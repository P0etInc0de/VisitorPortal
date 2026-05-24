<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Tasks;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use App\Notifications\Host\VisitReminderDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DailyVisitorReminder
{
    /**
     * Notify employees if they have visitors today
     * Shall run at 7am every day
     * Might be extended to notify the visitor as well...
     *
     * @throws \Throwable
     * */
    public function __invoke(): void
    {
        Log::info('DailyVisitorReminder started', [
            'class' => get_class(),
        ]);

        $visitsOfToday = Visit::query()
            ->where('visits.status', '=', VisitStatusEnum::Planned->value)
            ->whereDate('scheduled_from', Carbon::today())
            ->with('host', 'visitors')
            ->get();

        $visitsOfToday->groupBy('host_user_id')->each(function ($visitCollection) {
            $user = $visitCollection->first()->host;

            if (! $user?->is_active) {
                return;
            }

            // Map visits to their visitors keyed by visit ID
            $visitorCollection = $visitCollection->pluck('visitors', 'id');

            Notification::send($user, new VisitReminderDaily(
                $visitCollection,
                $visitorCollection
            ));
        });

        Log::info('DailyVisitorReminder finished', [
            'class' => get_class(),
        ]);
    }
}
