<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Requests\Monitor;

use App\Support\RasterImageUpload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonitorFallbackPageRequest extends FormRequest
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
            'heading' => 'required|string|max:255',
            'subheading' => 'nullable|string|max:255',
            'show_logo' => 'boolean',
            'show_date' => 'boolean',
            'background_source' => 'required|in:inherit,preset-1,preset-2,preset-3,upload',
            'image' => RasterImageUpload::rules(),
            'deleteImage' => 'boolean',
        ];
    }
}
