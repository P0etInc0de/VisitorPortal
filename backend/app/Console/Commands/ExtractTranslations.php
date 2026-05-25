<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use App\Support\UserPreferences;
use Illuminate\Console\Command;

class ExtractTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:extract-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract translation keys from project and update JSON language files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path(), \FilesystemIterator::SKIP_DOTS)
        );

        $foundKeys = [];

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $path = $file->getPathname();

            if (
                str_contains($path, 'vendor') ||
                str_contains($path, 'storage') ||
                str_contains($path, 'bootstrap/cache') ||
                str_contains($path, 'node_modules')
            ) {
                continue;
            }

            if (! is_readable($path)) {
                continue;
            }

            $content = @file_get_contents($path);

            // Match translation calls with quoted string literals.
            preg_match_all("/__\\(\s*['\"](.*?)['\"]/", $content, $matches);

            foreach ($matches[1] as $key) {
                if (str_contains($key, '::')) {
                    continue;
                }
                $foundKeys[$key] = $key;
            }
        }

        foreach (UserPreferences::supportedLocales() as $locale) {
            $path = lang_path($locale.'.json');
            $existing = file_exists($path)
                ? json_decode(file_get_contents($path), true)
                : [];

            if (! is_array($existing)) {
                $existing = [];
            }

            $merged = array_merge($foundKeys, $existing);
            ksort($merged);

            file_put_contents(
                $path,
                json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        $this->info('Translations extracted!');
    }
}
