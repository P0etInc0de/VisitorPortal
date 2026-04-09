<?php

namespace App\Models;

use App\Enums\VisitorStatusEnum;
use Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Visitor extends Model
{
    /** @use HasFactory<VisitorFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => VisitorStatusEnum::class,
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

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            (string) ($this->first_name ?? ''),
            (string) ($this->last_name ?? ''),
        ])));
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->full_name !== '') {
            return $this->full_name;
        }

        foreach ([$this->company, $this->email] as $fallback) {
            $fallback = trim((string) $fallback);

            if ($fallback !== '') {
                return $fallback;
            }
        }

        return '–';
    }
}
