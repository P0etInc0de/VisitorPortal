<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitorSlide extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'heading',
        'subheading',
        'slide_number',
        'show_logo',
        'show_date',
        'show_time',
        'visitors',
    ];

    protected $casts = [
        'visitors' => 'array',
    ];

    public function monitorSetting(): BelongsTo
    {
        return $this->belongsTo(Monitor::class, 'monitor_setting_id');
    }
}
