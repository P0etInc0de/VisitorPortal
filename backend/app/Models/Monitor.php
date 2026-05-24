<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Monitor extends Model
{
    public const DEFAULT_TRANSITION_TIME_MILLISECONDS = 5000;

    public const DEFAULT_AUTO_GENERATION_WINDOW_MINUTES = 30;

    public const DEFAULT_BACKGROUND_SOURCE = 'preset-1';

    public const DEFAULT_CONTENT_CARD_STYLE = 'transparent';

    public const DISPLAY_COMPANY_ONLY = 'company_only';

    public const DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL = 'title_first_name_last_initial';

    public const DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME = 'title_first_initial_last_name';

    public const DISPLAY_TITLE_FULL_NAME = 'title_full_name';

    public const DEFAULT_MONITOR_DISPLAY_MODE = self::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'name',
        'transition_time_milliseconds',
        'image_path',
        'background_source',
        'background_overlay_enabled',
        'header_text_is_light',
        'content_card_style',
        'auto_generation',
        'auto_generation_window_minutes',
        'monitor_display_mode',
        'fallback_heading',
        'fallback_subheading',
        'fallback_show_logo',
        'fallback_show_date',
        'fallback_image_path',
        'fallback_background_source',
    ];

    protected $casts = [
        'auto_generation' => 'boolean',
        'background_overlay_enabled' => 'boolean',
        'header_text_is_light' => 'boolean',
        'transition_time_milliseconds' => 'integer',
        'auto_generation_window_minutes' => 'integer',
        'fallback_show_logo' => 'boolean',
        'fallback_show_date' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $monitor) {
            if (blank($monitor->site_id)) {
                $monitor->site_id = Site::default()->id;
            }

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

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return array<string, int|bool|string>
     */
    public static function defaultSettings(): array
    {
        return [
            'transition_time_milliseconds' => self::DEFAULT_TRANSITION_TIME_MILLISECONDS,
            'auto_generation' => self::defaultAutoGeneration(),
            'auto_generation_window_minutes' => self::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES,
            'monitor_display_mode' => self::defaultMonitorDisplayMode(),
            'background_source' => self::DEFAULT_BACKGROUND_SOURCE,
            'background_overlay_enabled' => false,
            'header_text_is_light' => false,
            'content_card_style' => self::DEFAULT_CONTENT_CARD_STYLE,
            'fallback_heading' => self::defaultFallbackHeading(),
            'fallback_subheading' => self::defaultFallbackSubheading(),
            'fallback_show_logo' => true,
            'fallback_show_date' => true,
        ];
    }

    public static function defaultAutoGeneration(): bool
    {
        return (bool) config('branding.monitor_auto_generation', false);
    }

    public static function defaultMonitorDisplayMode(): string
    {
        $mode = (string) config('branding.monitor_display_mode', self::DEFAULT_MONITOR_DISPLAY_MODE);

        return in_array($mode, self::displayModes(), true) ? $mode : self::DEFAULT_MONITOR_DISPLAY_MODE;
    }

    public static function defaultFallbackHeading(): string
    {
        return (string) config('branding.monitor_fallback_heading', 'Welcome to VisitorPortal');
    }

    public static function defaultFallbackSubheading(): string
    {
        return (string) config('branding.monitor_fallback_subheading', "We're glad you're here.");
    }

    /**
     * @return array<string, string>
     */
    public static function displayModeOptions(): array
    {
        return [
            self::DISPLAY_COMPANY_ONLY => __('Nur Unternehmen'),
            self::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL => __('Titel + Vorname + erster Buchstabe Nachname'),
            self::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME => __('Titel + erster Buchstabe Vorname + Nachname'),
            self::DISPLAY_TITLE_FULL_NAME => __('Titel + Vorname + Nachname'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function displayModes(): array
    {
        return array_keys(self::displayModeOptions());
    }

    /**
     * @return array{id: string, heading: string, subheading: ?string, show_logo: bool, show_date: bool, visitors: array<int, array<string, mixed>>}
     */
    public function fallbackSlideData(): array
    {
        return [
            'id' => 'fallback',
            'heading' => $this->fallback_heading ?: self::defaultFallbackHeading(),
            'subheading' => $this->fallback_subheading ?: self::defaultFallbackSubheading(),
            'show_logo' => (bool) $this->fallback_show_logo,
            'show_date' => (bool) $this->fallback_show_date,
            'visitors' => [],
        ];
    }

    /**
     * @return EloquentCollection<int, MonitorSlide>
     */
    public function displaySlides(): EloquentCollection
    {
        $slidesQuery = $this->monitorSlides()
            ->where('is_active', true);

        if ($this->auto_generation) {
            $slidesQuery->where('is_auto_generated', true);
        } else {
            $slidesQuery->where('is_auto_generated', false);
        }

        return $slidesQuery
            ->orderBy('slide_number')
            ->get();
    }

    public function firstDisplayBackgroundAssetUrl(): ?string
    {
        $slide = $this->displaySlides()->first();

        return $slide
            ? $slide->backgroundAssetUrl($this)
            : $this->fallbackBackgroundAssetUrl();
    }

    public function fallbackResolvedBackgroundSource(): ?string
    {
        $presets = self::backgroundPresets();

        if ($this->fallback_background_source === 'upload' && filled($this->fallback_image_path) && Storage::disk('public')->exists($this->fallback_image_path)) {
            return 'upload';
        }

        if (array_key_exists((string) $this->fallback_background_source, $presets) && $this->fallback_background_source !== 'upload') {
            return (string) $this->fallback_background_source;
        }

        return null;
    }

    public function fallbackBackgroundAssetUrl(): ?string
    {
        $source = $this->fallbackResolvedBackgroundSource();

        if ($source === null) {
            return $this->backgroundAssetUrl();
        }

        if ($source === 'upload') {
            return asset('storage/'.$this->fallback_image_path);
        }

        $preset = self::backgroundPresets()[$source] ?? null;

        return $preset ? asset($preset['path']) : $this->backgroundAssetUrl();
    }

    /**
     * @return array<string, array{label: string, path: string, description: string}>
     */
    public static function backgroundPresets(): array
    {
        return [
            'preset-1' => [
                'label' => __('Standardbild 1'),
                'path' => 'images/monitor/monitor-default-1.jpg',
                'description' => __('Lager- und Logistikmotiv als vordefinierter Hintergrund.'),
            ],
            'preset-2' => [
                'label' => __('Standardbild 2'),
                'path' => 'images/monitor/monitor-default-2.jpg',
                'description' => __('Heller Raum mit ruhiger Fläche für kontrastarme Begrüßungsseiten.'),
            ],
            'preset-3' => [
                'label' => __('Standardbild 3'),
                'path' => 'images/monitor/monitor-default-3.jpg',
                'description' => __('Architekturmotiv mit viel Himmel für freundliche Empfangsseiten.'),
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
