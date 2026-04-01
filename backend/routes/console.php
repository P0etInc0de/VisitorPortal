<?php

use App\Tasks\DailyVisitorReminder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Test Scheduler via php artisan schedule:test
 * List Scheduler via php artisan schedule:list
 * Connect to mailhog to check if the Scheduler works
 */
Schedule::call(new DailyVisitorReminder)->dailyAt('07:00')->description('DailyVisitorReminder');
