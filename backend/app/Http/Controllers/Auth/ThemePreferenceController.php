<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\UserPreferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class ThemePreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $payload = $request->validate([
            'theme_preference' => ['required', 'string', 'in:'.implode(',', UserPreferences::supportedThemes())],
        ]);

        $themePreference = UserPreferences::normalizeTheme($payload['theme_preference'])
            ?? UserPreferences::THEME_SYSTEM;

        $user->forceFill(['theme_preference' => $themePreference])->save();
        Cookie::queue(UserPreferences::THEME_COOKIE, $themePreference, 60 * 24 * 365);

        return response()->json([
            'theme_preference' => $themePreference,
        ]);
    }
}
