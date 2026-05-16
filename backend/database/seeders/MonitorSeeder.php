<?php

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\Site;
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

        Monitor::query()->updateOrCreate([
            'site_id' => $site->id,
            'name' => 'WillkommensMonitor',
        ], Monitor::defaultSettings());
    }
}
