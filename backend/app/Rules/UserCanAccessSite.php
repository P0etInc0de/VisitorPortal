<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserCanAccessSite implements ValidationRule
{
    public function __construct(
        private readonly int $siteId,
        private readonly string $message,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $user = User::query()
            ->with('sites:id')
            ->where('is_active', true)
            ->whereKey((int) $value)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor']))
            ->first();

        if (! $user?->canAccessSite($this->siteId)) {
            $fail($this->message);
        }
    }
}
