<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataRetentionRun extends Model
{
    protected $fillable = [
        'command',
        'dry_run',
        'retention_days',
        'cutoff_at',
        'visits_matched',
        'visits_deleted',
        'visitors_matched',
        'visitors_deleted',
        'notifications_matched',
        'notifications_deleted',
        'monitor_slides_matched',
        'monitor_slides_deleted',
        'monitor_slides_anonymized',
        'recurring_series_matched',
        'recurring_series_deleted',
        'indefinite_legal_holds_matched',
        'status',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'dry_run' => 'boolean',
        'cutoff_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
