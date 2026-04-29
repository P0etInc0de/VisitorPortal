<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MonitorSlide extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'monitor_id',
        'heading',
        'subheading',
        'slide_number',
        'show_logo',
        'show_date',
        'show_time',
        'visitors',
        'image_path',
        'background_source',
    ];

    protected $casts = [
        'visitors' => 'array',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function resolvedBackgroundSource(): ?string
    {
        $presets = Monitor::backgroundPresets();

        if ($this->background_source === 'upload' && filled($this->image_path) && Storage::disk('public')->exists($this->image_path)) {
            return 'upload';
        }

        if (array_key_exists((string) $this->background_source, $presets) && $this->background_source !== 'upload') {
            return (string) $this->background_source;
        }

        return null;
    }

    public function backgroundAssetUrl(Monitor $monitor): ?string
    {
        $source = $this->resolvedBackgroundSource();

        if ($source === null) {
            return $monitor->backgroundAssetUrl();
        }

        if ($source === 'upload') {
            return asset('storage/'.$this->image_path);
        }

        $preset = Monitor::backgroundPresets()[$source] ?? null;

        return $preset ? asset($preset['path']) : $monitor->backgroundAssetUrl();
    }

    protected static function booted(): void
    {
        // Automatically assign the next number on creation
        static::creating(function ($model) {
            if (! $model->slide_number) {
                $model->slide_number = static::where('monitor_id', $model->monitor_id)->max('slide_number') + 1;
            } else {
                static::where('monitor_id', $model->monitor_id)
                    ->where('slide_number', '>=', $model->slide_number)
                    ->orderBy('slide_number', 'desc')
                    ->increment('slide_number');
            }
        });

        static::updating(function ($model) {
            // Only run if slide_number is changed
            if ($model->isDirty('slide_number')) {

                $old_number = $model->getOriginal('slide_number');
                $new_number = $model->slide_number;

                // If moving the slide DOWN the list (e.g., from position 2 to 4)
                if ($new_number > $old_number) {
                    static::where('monitor_id', $model->monitor_id)
                        ->where('slide_number', '>', $old_number)
                        ->where('slide_number', '<=', $new_number)
                        ->decrement('slide_number');
                }
                // If moving the slide UP the list (e.g., from position 4 to 2)
                elseif ($new_number < $old_number) {
                    static::where('monitor_id', $model->monitor_id)
                        ->where('slide_number', '>=', $new_number)
                        ->where('slide_number', '<', $old_number)
                        ->increment('slide_number');
                }
            }
        });

        // Re-order remaining records after a deletion
        static::deleted(function ($model) {
            static::where('monitor_id', $model->monitor_id)
                ->where('slide_number', '>', $model->slide_number)
                ->decrement('slide_number');
        });
    }
}
