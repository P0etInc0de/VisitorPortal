<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Middleware;

use App\Support\UserPreferences;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserPreferences
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        $themePreference = $this->resolveThemePreference($request);
        $effectiveTheme = $this->resolveEffectiveTheme($request);

        App::setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        View::share('themePreference', $themePreference);
        View::share('theme', UserPreferences::initialTheme($themePreference, $effectiveTheme));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $userLocale = UserPreferences::normalizeLocale($request->user()?->locale);

        if ($userLocale) {
            return $userLocale;
        }

        $sessionLocale = $request->hasSession()
            ? UserPreferences::normalizeLocale($request->session()->get('locale'))
            : null;

        if ($sessionLocale) {
            return $sessionLocale;
        }

        $cookieLocale = UserPreferences::normalizeLocale($request->cookie(UserPreferences::LOCALE_COOKIE));

        if ($cookieLocale) {
            return $cookieLocale;
        }

        return UserPreferences::browserLocale($request);
    }

    private function resolveThemePreference(Request $request): string
    {
        $userTheme = UserPreferences::normalizeTheme($request->user()?->theme_preference);

        if ($userTheme) {
            return $userTheme;
        }

        return UserPreferences::normalizeTheme($request->cookie(UserPreferences::THEME_COOKIE))
            ?? UserPreferences::THEME_SYSTEM;
    }

    private function resolveEffectiveTheme(Request $request): ?string
    {
        return UserPreferences::normalizeEffectiveTheme(
            $this->rawCookie($request, UserPreferences::THEME_EFFECTIVE_COOKIE)
        );
    }

    private function rawCookie(Request $request, string $name): ?string
    {
        $cookieHeader = $request->headers->get('cookie');

        if (! $cookieHeader) {
            return null;
        }

        foreach (explode(';', $cookieHeader) as $cookie) {
            [$cookieName, $cookieValue] = array_pad(explode('=', trim($cookie), 2), 2, null);

            if ($cookieName === $name && $cookieValue !== null) {
                return rawurldecode($cookieValue);
            }
        }

        return null;
    }
}
