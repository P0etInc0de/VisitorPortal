<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;

trait RateLimitsSearch
{
    protected function enforceSearchRateLimit(string $scope): void
    {
        $maxAttempts = max(1, (int) config('privacy.search_rate_limit_per_minute', 60));
        $identity = auth()->id() ? 'user:'.auth()->id() : 'ip:'.request()->ip();
        $key = 'livewire-search:'.$scope.':'.$identity;

        // Debounced Livewire searches still need server-side abuse protection.
        abort_if(RateLimiter::tooManyAttempts($key, $maxAttempts), 429, __('Zu viele Suchanfragen.'));

        RateLimiter::hit($key, 60);
    }
}
