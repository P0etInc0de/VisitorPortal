<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'responsible_user_id',
        'scheduled_from',
        'scheduled_until',
        'status',
        'notes',
        'canceled_at',
        'canceled_by_user_id',
    ];

    protected $casts = [
        'scheduled_from' => 'datetime',
        'scheduled_until' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    protected $appends = [
        'display_title',
        'participant_count',
        'badge_pending_count',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
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
