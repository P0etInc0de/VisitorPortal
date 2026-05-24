<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\Site;
use App\Tasks\WelcomeMonitorAutoGeneration;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo monitors are disabled in production.');
        }

        $site = Site::default();

        $monitor = Monitor::query()->updateOrCreate([
            'site_id' => $site->id,
            'name' => 'WillkommensMonitor',
        ], [
            ...Monitor::defaultSettings(),
            'auto_generation' => true,
            'auto_generation_window_minutes' => 180,
        ]);

        app(WelcomeMonitorAutoGeneration::class)($monitor);
    }
}
