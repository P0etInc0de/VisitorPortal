<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Enums\VisitorStatusEnum;
use Database\Factories\VisitorFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
        'gender',
        'email',
        'phone',
        'company',
        'status',
        'notes',
    ];

    protected $casts = [
        'gender' => GenderEnum::class,
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

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => VisitorStatusEnum::tryFrom(Str::lower((string) $value)) ?? VisitorStatusEnum::Planned,
            set: fn (mixed $value) => $value instanceof VisitorStatusEnum
                ? $value->value
                : (VisitorStatusEnum::tryFrom(Str::lower((string) $value)) ?? VisitorStatusEnum::Planned)->value,
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            (string) ($this->first_name ?? ''),
            (string) ($this->name ?? ''),
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

    /**
     * Get next scheduled visit
     */
    public function nextVisit()
    {
        return $this->visits()
            ->where('scheduled_from', '>', Carbon::now())
            ->orderBy('scheduled_from', 'asc')
            ->first();
    }
}
