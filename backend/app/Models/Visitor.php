<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use App\Enums\SalutationEnum;
use Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class Visitor extends Model
{
    /** @use HasFactory<VisitorFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'name',
        'title',
        'salutation',
        'email',
        'phone',
        'company',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'salutation' => SalutationEnum::class,
    ];

    public function visits(): BelongsToMany
    {
        return $this->belongsToMany(Visit::class, 'visit_visitor')
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('ViewAny:Visitor') || $user->can('EditAny:Visitor') || $user->can('UpdateAny:Visitor') || $user->can('DeleteAny:Visitor')) {
            return $query;
        }

        $canSeeOwnVisits = $user->can('ViewKnown:Visitor') || $user->can('ViewOwn:Visit') || $user->can('EditOwn:Visit') || $user->can('DeleteOwn:Visit');
        $canSeeDepartmentVisits = $user->department_id !== null
            && ($user->can('ViewDepartment:Visitor') || $user->can('ViewDepartment:Visit') || $user->can('EditDepartment:Visit') || $user->can('DeleteDepartment:Visit'));
        $assignedSiteIds = $user->assignedSiteIds();
        $canSeeSiteVisits = $assignedSiteIds->isNotEmpty()
            && ($user->can('ViewSite:Visitor') || $user->can('ViewSite:Visit') || $user->can('EditSite:Visit') || $user->can('DeleteSite:Visit'));

        return $query->where(function (Builder $query) use ($user, $assignedSiteIds, $canSeeOwnVisits, $canSeeDepartmentVisits, $canSeeSiteVisits): void {
            $query->where('created_by_user_id', $user->id);

            if (! $canSeeOwnVisits && ! $canSeeDepartmentVisits && ! $canSeeSiteVisits) {
                return;
            }

            $query->orWhereHas('visits', function (Builder $query) use ($user, $assignedSiteIds, $canSeeOwnVisits, $canSeeDepartmentVisits, $canSeeSiteVisits): void {
                $query->where(function (Builder $query) use ($user, $assignedSiteIds, $canSeeOwnVisits, $canSeeDepartmentVisits, $canSeeSiteVisits): void {
                    if ($canSeeSiteVisits) {
                        $query->orWhereIn('visits.site_id', $assignedSiteIds->all());
                    }

                    if ($canSeeOwnVisits) {
                        $query->orWhere('visits.host_user_id', $user->id)
                            ->orWhere('visits.substitute_user_id', $user->id);
                    }

                    if ($canSeeDepartmentVisits) {
                        $query->orWhereHas('host', fn (Builder $query) => $query->where('department_id', $user->department_id))
                            ->orWhereHas('substituteUser', fn (Builder $query) => $query->where('department_id', $user->department_id));
                    }
                });
            });
        });
    }
}
