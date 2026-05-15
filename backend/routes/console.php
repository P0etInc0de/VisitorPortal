<?php

use App\Tasks\DailyVisitorReminder;
use App\Tasks\RecurringVisitSeriesExpansion;
use App\Tasks\WelcomeMonitorAutoGeneration;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Test Scheduler via php artisan schedule:test
 * List Scheduler via php artisan schedule:list
 * Connect to mailhog to check if the Scheduler works
 */
Schedule::call(new DailyVisitorReminder)->dailyAt('07:00')->description('DailyVisitorReminder');
Schedule::call(new WelcomeMonitorAutoGeneration)->everyMinute()->description('WelcomeMonitorAutoGeneration');
Schedule::call(new RecurringVisitSeriesExpansion)->dailyAt('02:30')->description('RecurringVisitSeriesExpansion');

if (config('privacy.purge_enabled')) {
    Schedule::command('visits:purge-expired')
        ->dailyAt('03:15')
        ->withoutOverlapping()
        ->description('PurgeExpiredVisits');
}
