<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

use App\Support\OperationalHeartbeat;
use App\Tasks\CompleteFinishedVisits;
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
Schedule::call(new CompleteFinishedVisits)->everyMinute()->description('CompleteFinishedVisits');
Schedule::call(new RecurringVisitSeriesExpansion)->dailyAt('02:30')->description('RecurringVisitSeriesExpansion');
Schedule::call(function (): void {
    app(OperationalHeartbeat::class)->markSchedulerRun();
})
    ->name('visitorportal.scheduler-heartbeat')
    ->everyMinute();

if (config('privacy.purge_enabled')) {
    Schedule::command('visits:purge-expired')
        ->dailyAt('03:15')
        ->withoutOverlapping()
        ->description('PurgeExpiredVisits');
}

if (config('privacy.technical_retention.enabled')) {
    Schedule::command('privacy:purge-technical-data')
        ->dailyAt('03:45')
        ->name('privacy-purge-technical-data')
        ->withoutOverlapping();
}
