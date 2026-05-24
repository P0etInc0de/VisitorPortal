<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Requests;

use App\Models\User;
use App\Support\UserPreferences;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->can('editAny', User::class) || $this->user()->can('editDepartment', $this->user()) || $this->user()->can('editOwn', $this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', Rule::in(UserPreferences::supportedLocales())],
            'theme_preference' => ['sometimes', 'string', Rule::in(UserPreferences::supportedThemes())],
        ];
    }
}
