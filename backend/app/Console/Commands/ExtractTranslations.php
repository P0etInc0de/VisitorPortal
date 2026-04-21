<?php

namespace App\Console\Commands;

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
    protected $description = 'Extract translation keys from project and update de.json';

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

            preg_match_all("/__\\(['\"]([^'\"]+)['\"]\\)/", $content, $matches);

            foreach ($matches[1] as $key) {
                if (str_contains($key, '::')) {
                    continue;
                }
                $foundKeys[$key] = $key;
            }
        }

        $path = lang_path('de.json');
        $path_en = lang_path('en.json');

        $existing = file_exists($path)
            ? json_decode(file_get_contents($path), true)
            : [];

        $existing_en = file_exists($path_en)
            ? json_decode(file_get_contents($path_en), true)
            : [];

        if (! is_array($existing)) {
            $existing = [];
        }

        if (! is_array($existing_en)) {
            $existing_en = [];
        }
        $merged = array_merge($foundKeys, $existing);
        $merged_en = array_merge($foundKeys, $existing_en);

        ksort($merged);
        ksort($merged_en);

        file_put_contents(
            $path,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        file_put_contents(
            $path_en,
            json_encode($merged_en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->info('Translations extracted!');
    }
}
