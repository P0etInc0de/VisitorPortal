<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SsoAdapterBoundaryTest extends TestCase
{
    public function test_facile_library_is_only_used_inside_facile_adapter(): void
    {
        $allowed = realpath(app_path('Auth/Sso/Facile/FacileOidcAuthenticator.php'));

        foreach (File::allFiles(app_path()) as $file) {
            if ($file->getRealPath() === $allowed) {
                continue;
            }

            $contents = $file->getContents();

            $this->assertStringNotContainsString('use Facile\\', $contents, $file->getRealPath());
            $this->assertStringNotContainsString('\\Facile\\OpenIDClient', $contents, $file->getRealPath());
            $this->assertStringNotContainsString('\\Facile\\JoseVerifier', $contents, $file->getRealPath());
        }
    }
}
