<?php

namespace Database\Seeders;

use App\Models\Monitor;
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

        Monitor::query()->updateOrCreate([
            'name' => 'WillkommensMonitor',
        ], Monitor::defaultSettings());
    }
}
