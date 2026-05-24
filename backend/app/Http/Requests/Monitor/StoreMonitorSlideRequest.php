<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Requests\Monitor;

use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Support\RasterImageUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonitorSlideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $monitor = $this->route('monitor');

        return $this->user()->can('create', MonitorSlide::class) && $this->user()->can('update', $monitor);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $monitor = $this->route('monitor');
        $maxSlide = MonitorSlide::query()
            ->where('monitor_id', $monitor->id)
            ->where('is_auto_generated', false)
            ->max('slide_number') + 1;

        return [
            'visitors' => ['nullable', 'json'],
            'heading' => 'required',
            'slide_number' => [
                'required',
                'integer',
                'between:1,'.$maxSlide,
            ],
            'is_active' => 'boolean',
            'monitor_display_mode' => ['required', 'string', 'in:'.implode(',', Monitor::displayModes())],
            'show_logo' => 'boolean',
            'show_date' => 'boolean',
            'show_time' => 'boolean',
            'subheading' => 'string|nullable',
            'background_source' => 'required|in:inherit,preset-1,preset-2,preset-3,upload',
            'image' => RasterImageUpload::rules(),
            'deleteImage' => 'boolean',
        ];
    }
}
