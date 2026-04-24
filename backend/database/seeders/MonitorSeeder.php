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
        Monitor::query()->updateOrCreate([
            'monitors.name' => 'WillkommensMonitor',
            'monitors.transition_time_milliseconds' => 5000,
        ]);
    }
}
