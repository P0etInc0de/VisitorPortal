<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'transition_time_milliseconds',
        'image_path',
    ];

    public function monitorSlides(): HasMany
    {
        return $this->hasMany(MonitorSlide::class);
    }
}
