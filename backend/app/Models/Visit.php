<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use LogicException;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'site_id',
        'recurring_visit_series_id',
        'recurrence_occurrence_number',
        'recurrence_original_scheduled_from',
        'recurrence_is_modified',
        'host_user_id',
        'substitute_user_id',
        'created_by_user_id',
        'scheduled_from',
        'scheduled_until',
        'status',
        'is_confidential',
        'is_walk_in',
        'notes',
        'canceled_at',
        'canceled_by_user_id',
        'retention_hold_until',
        'retention_hold_reason',
        'retention_hold_by_user_id',
    ];

    protected $casts = [
        'scheduled_from' => 'datetime',
        'scheduled_until' => 'datetime',
        'recurrence_original_scheduled_from' => 'datetime',
        'recurrence_is_modified' => 'boolean',
        'is_confidential' => 'boolean',
        'is_walk_in' => 'boolean',
        'canceled_at' => 'datetime',
        'retention_hold_until' => 'datetime',
    ];

    protected $appends = [
        'display_title',
        'participant_count',
        'badge_pending_count',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function substituteUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_user_id');
    }

    public function recurringSeries(): BelongsTo
    {
        return $this->belongsTo(RecurringVisitSeries::class, 'recurring_visit_series_id');
    }

    public function canceledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'canceled_by_user_id');
    }

    public function retentionHoldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retention_hold_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function visitors(): BelongsToMany
    {
        return $this->belongsToMany(Visitor::class)
            ->using(VisitVisitor::class)
            ->withPivot([
                'badge_printed_at',
                'checked_in_at',
                'checked_out_at',
                'checked_in_by_user_id',
                'checked_out_by_user_id',
                'notes',
            ])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (self $visit): void {
            if (blank($visit->site_id)) {
                throw new LogicException('Visit site_id must be set explicitly.');
            }
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('ViewAny:Visit') || $user->can('EditAny:Visit') || $user->can('DeleteAny:Visit')) {
            return $query;
        }

        $assignedSiteIds = $user->assignedSiteIds();
        $canSeeSite = $assignedSiteIds->isNotEmpty()
            && ($user->can('ViewSite:Visit') || $user->can('EditSite:Visit') || $user->can('DeleteSite:Visit'));
        $canSeeDepartment = $user->department_id !== null
            && ($user->can('ViewDepartment:Visit') || $user->can('EditDepartment:Visit') || $user->can('DeleteDepartment:Visit'));
        $canSeeOwn = $user->can('ViewOwn:Visit') || $user->can('EditOwn:Visit') || $user->can('DeleteOwn:Visit');

        if (! $canSeeSite && ! $canSeeDepartment && ! $canSeeOwn) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($user, $assignedSiteIds, $canSeeSite, $canSeeDepartment, $canSeeOwn): void {
            if ($canSeeSite) {
                $query->orWhereIn('site_id', $assignedSiteIds->all());
            }

            if ($canSeeDepartment) {
                $query->orWhereHas('host', fn (Builder $query) => $query->where('department_id', $user->department_id))
                    ->orWhereHas('substituteUser', fn (Builder $query) => $query->where('department_id', $user->department_id));
            }

            if ($canSeeOwn) {
                $query->orWhere('host_user_id', $user->id)
                    ->orWhere('substitute_user_id', $user->id);
            }
        });
    }

    public function getDisplayTitleAttribute(): string
    {
        return filled($this->title) ? $this->title : 'Besuch';
    }

    public function getParticipantCountAttribute(): int
    {
        if ($this->relationLoaded('visitors')) {
            return $this->visitors->count();
        }

        return $this->visitors()->count();
    }

    public function getBadgePendingCountAttribute(): int
    {
        $visitors = $this->relationLoaded('visitors')
            ? $this->visitors
            : $this->visitors()->get();

        return $visitors->filter(function (Visitor $visitor) {
            return blank($visitor->pivot?->badge_printed_at);
        })->count();
    }

    public function participantPreview(int $limit = 3): Collection
    {
        $visitors = $this->relationLoaded('visitors')
            ? $this->visitors
            : $this->visitors()->orderBy('first_name')->orderBy('name')->get();

        return $visitors->take($limit)->values();
    }
}
