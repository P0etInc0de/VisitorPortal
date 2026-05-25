<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Http\Request;

class UserPreferences
{
    public const THEME_LIGHT = 'light';

    public const THEME_DARK = 'dark';

    public const THEME_TRUE_BLACK = 'true-black';

    public const THEME_SYSTEM = 'system';

    public const THEME_COOKIE = 'theme_preference';

    public const THEME_EFFECTIVE_COOKIE = 'theme_effective';

    public const LOCALE_COOKIE = 'locale';

    public const LOCALE_EN = 'en';

    public const LOCALE_DE = 'de';

    public const LOCALE_FR = 'fr';

    public const LOCALE_CS = 'cs';

    /**
     * @return list<string>
     */
    public static function supportedThemes(): array
    {
        return [
            self::THEME_LIGHT,
            self::THEME_DARK,
            self::THEME_TRUE_BLACK,
            self::THEME_SYSTEM,
        ];
    }

    /**
     * @return list<string>
     */
    public static function supportedEffectiveThemes(): array
    {
        return [
            self::THEME_LIGHT,
            self::THEME_DARK,
        ];
    }

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return [
            self::LOCALE_DE,
            self::LOCALE_EN,
            self::LOCALE_FR,
            self::LOCALE_CS,
        ];
    }

    public static function normalizeTheme(?string $theme): ?string
    {
        return in_array($theme, self::supportedThemes(), true) ? $theme : null;
    }

    public static function normalizeEffectiveTheme(?string $theme): ?string
    {
        return in_array($theme, self::supportedEffectiveThemes(), true) ? $theme : null;
    }

    public static function initialTheme(?string $themePreference, ?string $effectiveTheme = null): string
    {
        $normalizedPreference = self::normalizeTheme($themePreference) ?? self::THEME_SYSTEM;

        if ($normalizedPreference === self::THEME_SYSTEM) {
            return self::normalizeEffectiveTheme($effectiveTheme) ?? self::THEME_LIGHT;
        }

        return $normalizedPreference;
    }

    public static function normalizeLocale(?string $locale): ?string
    {
        return in_array($locale, self::supportedLocales(), true) ? $locale : null;
    }

    public static function browserLocale(Request $request): string
    {
        $supportedLocales = self::supportedLocales();

        foreach ($request->getLanguages() as $language) {
            $normalizedLanguage = strtolower(str_replace('_', '-', $language));
            $baseLocale = explode('-', $normalizedLanguage)[0];

            if (in_array($baseLocale, $supportedLocales, true)) {
                return $baseLocale;
            }
        }

        return self::LOCALE_EN;
    }
}
