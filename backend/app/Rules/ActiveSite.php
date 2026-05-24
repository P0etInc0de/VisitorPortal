<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Rules;

use App\Models\Site;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveSite implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Site::query()->active()->whereKey((int) $value)->exists()) {
            $fail(__('Der ausgewählte Standort ist nicht aktiv.'));
        }
    }
}
