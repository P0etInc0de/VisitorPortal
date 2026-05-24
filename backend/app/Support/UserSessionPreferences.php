<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class UserSessionPreferences
{
    public function apply(Request $request, User $user): void
    {
        $locale = UserPreferences::normalizeLocale($user->locale)
            ?? UserPreferences::normalizeLocale($request->cookie(UserPreferences::LOCALE_COOKIE))
            ?? UserPreferences::browserLocale($request);
        $themePreference = UserPreferences::normalizeTheme($user->theme_preference)
            ?? UserPreferences::normalizeTheme($request->cookie(UserPreferences::THEME_COOKIE))
            ?? UserPreferences::THEME_SYSTEM;

        $request->session()->put('locale', $locale);
        Cookie::queue(UserPreferences::LOCALE_COOKIE, $locale, 60 * 24 * 365);
        Cookie::queue(UserPreferences::THEME_COOKIE, $themePreference, 60 * 24 * 365);
    }
}
