<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Requests\Monitor;

use App\Models\Monitor;
use App\Support\RasterImageUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $monitor = $this->route('monitor');

        return $this->user()->can('update', $monitor);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transition_time_milliseconds' => 'required|integer|between:1000,50000',
            'auto_generation_window_minutes' => 'required|integer|between:1,180',
            'monitor_display_mode' => ['required', 'string', 'in:'.implode(',', Monitor::displayModes())],
            'background_source' => 'required|in:preset-1,preset-2,preset-3,upload',
            'background_overlay_enabled' => 'boolean',
            'content_card_style' => 'required|in:solid,transparent,none',
            'fallback_heading' => 'required|string|max:255',
            'fallback_subheading' => 'nullable|string|max:255',
            'fallback_show_logo' => 'boolean',
            'fallback_show_date' => 'boolean',
            'image' => RasterImageUpload::rules(),
            'deleteImage' => 'boolean',
            'auto_generation' => 'boolean',
        ];
    }
}
