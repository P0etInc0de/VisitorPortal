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

class ActiveSites implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $siteIds = collect($value ?? [])
            ->filter(fn ($siteId) => filled($siteId))
            ->map(fn ($siteId): int => (int) $siteId)
            ->unique()
            ->values();

        if ($siteIds->isEmpty()) {
            return;
        }

        $activeCount = Site::query()
            ->active()
            ->whereIn('id', $siteIds->all())
            ->count();

        if ($activeCount !== $siteIds->count()) {
            $fail(__('Zugeordnete Standorte müssen aktiv sein.'));
        }
    }
}
