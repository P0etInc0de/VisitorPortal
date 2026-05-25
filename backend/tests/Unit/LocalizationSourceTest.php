<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use App\Support\UserPreferences;
use Tests\TestCase;

class LocalizationSourceTest extends TestCase
{
    public function test_supported_locales_include_french_and_czech(): void
    {
        $this->assertSame(['de', 'en', 'fr', 'cs'], UserPreferences::supportedLocales());
    }

    public function test_supported_json_translation_files_are_valid_and_complete(): void
    {
        $referenceKeys = array_unique(array_merge(
            array_keys($this->readJsonLanguageFile('de')),
            array_keys($this->readJsonLanguageFile('en')),
        ));

        foreach (UserPreferences::supportedLocales() as $locale) {
            $translations = $this->readJsonLanguageFile($locale);
            $missingKeys = array_values(array_diff($referenceKeys, array_keys($translations)));

            $this->assertSame([], $missingKeys, "Missing {$locale}.json translation keys.");
        }
    }

    /**
     * @return array<string, string>
     */
    private function readJsonLanguageFile(string $locale): array
    {
        $path = lang_path($locale.'.json');

        $this->assertFileExists($path);

        $translations = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($translations);

        return $translations;
    }
}
