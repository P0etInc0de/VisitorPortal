<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class RecurringVisitSeries extends Model
{
    protected $table = 'recurring_visit_series';

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_YEARLY = 'yearly';

    public const FREQUENCY_EVERY_X_DAYS = 'every_x_days';

    public const END_DATE = 'date';

    public const END_COUNT = 'count';

    public const END_FOREVER = 'forever';

    public const UPDATE_SINGLE = 'single';

    public const UPDATE_FUTURE = 'future';

    public const UPDATE_SERIES = 'series';

    public const FOREVER_HORIZON_MONTHS = 30;

    protected $fillable = [
        'title',
        'site_id',
        'host_user_id',
        'substitute_user_id',
        'created_by_user_id',
        'status',
        'is_confidential',
        'notes',
        'starts_at',
        'duration_minutes',
        'frequency',
        'interval_days',
        'ends',
        'end_date',
        'occurrence_count',
        'generated_until',
        'exclusion_dates',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'end_date' => 'date',
        'generated_until' => 'datetime',
        'exclusion_dates' => 'array',
        'is_confidential' => 'boolean',
        'duration_minutes' => 'integer',
        'interval_days' => 'integer',
        'occurrence_count' => 'integer',
    ];

    /**
     * @return array<string, string>
     */
    public static function frequencyOptions(): array
    {
        return [
            self::FREQUENCY_DAILY => __('Täglich'),
            self::FREQUENCY_WEEKLY => __('Wöchentlich'),
            self::FREQUENCY_MONTHLY => __('Monatlich'),
            self::FREQUENCY_YEARLY => __('Jährlich'),
            self::FREQUENCY_EVERY_X_DAYS => __('Alle X Tage'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function endOptions(): array
    {
        return [
            self::END_DATE => __('Bis Datum'),
            self::END_COUNT => __('Anzahl Termine insgesamt'),
            self::END_FOREVER => __('Ohne Ende'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function frequencies(): array
    {
        return array_keys(self::frequencyOptions());
    }

    /**
     * @return list<string>
     */
    public static function ends(): array
    {
        return array_keys(self::endOptions());
    }

    /**
     * @return list<string>
     */
    public static function updateScopes(): array
    {
        return [self::UPDATE_SINGLE, self::UPDATE_FUTURE, self::UPDATE_SERIES];
    }

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'recurring_visit_series_id');
    }

    public function visitors(): BelongsToMany
    {
        return $this->belongsToMany(Visitor::class, 'recurring_visit_series_visitor')
            ->withPivot('notes')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::creating(function (self $series): void {
            if (blank($series->site_id)) {
                throw new LogicException('Recurring visit series site_id must be set explicitly.');
            }
        });
    }
}
