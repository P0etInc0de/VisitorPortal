<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use App\Support\VisitorPortalPermissions;
use Illuminate\Database\Seeder;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Shield demo permissions are disabled in production.');
        }

        VisitorPortalPermissions::sync($this->command);
    }
}
