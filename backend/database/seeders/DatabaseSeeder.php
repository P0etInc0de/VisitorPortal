<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Seeding is disabled in production.');
        }

        /*
        * The ShieldSeeder is auto generated:
        * - Scan the app/Filament folder for resources and generate CRUD permissions: `php artisan shield:generate --all`
        * - Auto-Generate a Seeder for the current shield database: `php artisan shield:seeder`
        */
        $this->call([
            SiteSeeder::class,
            ShieldSeeder::class,
            RoleSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            VisitorSeeder::class,
            VisitSeeder::class,
            MonitorSeeder::class,
        ]);
    }
}
