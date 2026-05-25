<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use Tests\TestCase;

class FaviconSourceTest extends TestCase
{
    public function test_favicon_partial_and_assets_exist(): void
    {
        $this->assertFileExists(resource_path('views/partials/favicons.blade.php'));

        foreach ([
            'favicon.ico',
            'favicon-16x16.png',
            'favicon-32x32.png',
            'apple-touch-icon.png',
            'android-chrome-192x192.png',
            'android-chrome-512x512.png',
            'site.webmanifest',
        ] as $asset) {
            $this->assertFileExists(public_path($asset));
        }
    }

    public function test_favicon_partial_references_expected_assets(): void
    {
        $source = (string) file_get_contents(resource_path('views/partials/favicons.blade.php'));

        foreach ([
            "asset('favicon.ico')",
            "asset('favicon-32x32.png')",
            "asset('favicon-16x16.png')",
            "asset('apple-touch-icon.png')",
            "asset('site.webmanifest')",
            'name="theme-color"',
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $source);
        }
    }

    public function test_favicon_partial_is_included_without_breaking_page_rendering_when_missing(): void
    {
        foreach ([
            'resources/views/layouts/app.blade.php',
            'resources/views/layouts/guest.blade.php',
            'resources/views/monitor/show.blade.php',
            'resources/views/components/filament/admin-theme-head.blade.php',
        ] as $view) {
            $this->assertStringContainsString("@includeIf('partials.favicons')", $this->source($view));
        }
    }

    private function source(string $path): string
    {
        return (string) file_get_contents(base_path($path));
    }
}
