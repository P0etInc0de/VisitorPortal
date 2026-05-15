<?php

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
