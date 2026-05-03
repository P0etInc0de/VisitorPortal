<?php

namespace App\Support;

use Illuminate\Http\Request;

class UserPreferences
{
    public const THEME_LIGHT = 'light';

    public const THEME_DARK = 'dark';

    public const THEME_TRUE_BLACK = 'true-black';

    public const THEME_SYSTEM = 'system';

    public const THEME_COOKIE = 'theme_preference';

    public const LOCALE_COOKIE = 'locale';

    public const LOCALE_EN = 'en';

    public const LOCALE_DE = 'de';

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

    public static function initialTheme(?string $themePreference): string
    {
        return match (self::normalizeTheme($themePreference)) {
            self::THEME_DARK => self::THEME_DARK,
            self::THEME_TRUE_BLACK => self::THEME_TRUE_BLACK,
            default => self::THEME_LIGHT,
        };
    }

    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        return ['de', 'en'];
    }

    public static function normalizeTheme(?string $theme): ?string
    {
        return in_array($theme, self::supportedThemes(), true) ? $theme : null;
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
