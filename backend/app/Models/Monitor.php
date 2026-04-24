<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Monitor extends Model
{
    public const DEFAULT_TRANSITION_TIME_MILLISECONDS = 5000;

    public const DEFAULT_AUTO_GENERATION_WINDOW_MINUTES = 30;

    public const DEFAULT_BACKGROUND_SOURCE = 'preset-1';

    public const DEFAULT_CONTENT_CARD_STYLE = 'transparent';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'transition_time_milliseconds',
        'image_path',
        'background_source',
        'background_overlay_enabled',
        'content_card_style',
        'auto_generation',
        'auto_generation_window_minutes',
    ];

    protected $casts = [
        'auto_generation' => 'boolean',
        'background_overlay_enabled' => 'boolean',
        'transition_time_milliseconds' => 'integer',
        'auto_generation_window_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $monitor) {
            foreach (self::defaultSettings() as $attribute => $value) {
                if ($monitor->{$attribute} === null) {
                    $monitor->{$attribute} = $value;
                }
            }
        });
    }

    public function monitorSlides(): HasMany
    {
        return $this->hasMany(MonitorSlide::class);
    }

    /**
     * @return array<string, int|bool|string>
     */
    public static function defaultSettings(): array
    {
        return [
            'transition_time_milliseconds' => self::DEFAULT_TRANSITION_TIME_MILLISECONDS,
            'auto_generation' => true,
            'auto_generation_window_minutes' => self::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES,
            'background_source' => self::DEFAULT_BACKGROUND_SOURCE,
            'background_overlay_enabled' => false,
            'content_card_style' => self::DEFAULT_CONTENT_CARD_STYLE,
        ];
    }

    /**
     * @return array<string, array{label: string, path: string, description: string}>
     */
    public static function backgroundPresets(): array
    {
        return [
            'preset-1' => [
                'label' => __('Standard 1'),
                'path' => 'images/monitor-default-1.svg',
                'description' => __('Helle Farbverläufe für einen ruhigen Empfangsbildschirm.'),
            ],
            'preset-2' => [
                'label' => __('Standard 2'),
                'path' => 'images/monitor-default-2.svg',
                'description' => __('Warme neutrale Flächen mit klaren Kontrasten für Textinhalte.'),
            ],
            'upload' => [
                'label' => __('Eigenes Bild'),
                'path' => '',
                'description' => __('Nutzen Sie ein eigenes JPG, PNG oder WebP im Format 16:9 für die beste Darstellung.'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function contentCardStyles(): array
    {
        return [
            'solid' => __('Standard'),
            'transparent' => __('Transparent'),
            'none' => __('Ohne Hintergrund'),
        ];
    }

    public function resolvedBackgroundSource(): string
    {
        $presets = self::backgroundPresets();

        if ($this->background_source === 'upload' && filled($this->image_path) && Storage::disk('public')->exists($this->image_path)) {
            return 'upload';
        }

        if (array_key_exists((string) $this->background_source, $presets) && $this->background_source !== 'upload') {
            return (string) $this->background_source;
        }

        return self::DEFAULT_BACKGROUND_SOURCE;
    }

    public function backgroundAssetUrl(): ?string
    {
        $source = $this->resolvedBackgroundSource();

        if ($source === 'upload') {
            return asset('storage/'.$this->image_path);
        }

        $preset = self::backgroundPresets()[$source] ?? null;

        return $preset ? asset($preset['path']) : null;
    }
}
