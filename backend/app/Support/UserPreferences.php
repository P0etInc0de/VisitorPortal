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
        return $request->getPreferredLanguage(self::supportedLocales()) ?: 'en';
    }
}
