<?php

namespace App\Tasks;


use App\Models\User;
use App\Models\Visit;
use App\Notifications\Guest\VisitCreated;
use App\Notifications\Host\GuestCheckedIn;
use App\Notifications\Host\SingleVisitReminder;
use App\Notifications\Host\VisitReminderDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DailyVisitorReminder
{
    /*
     * Notify employees if they have visitors today
     * Shall run at 7am every day
     * Might be extended to notify the visitor as well...
     * */
    public function __invoke(): void
    {
        $visitsOfToday = Visit::query()
            ->whereDate('visits.scheduled_from', Carbon::today())
            ->get();

        $visitsOfUsers = $visitsOfToday->groupBy('visits.responsible_user_id');

        foreach($visitsOfUsers as $visitCollection)
        {
            $user = $visitCollection->first()->responsibleUser;
            Notification::send($user, new VisitReminderDaily($visitCollection, $user));
        }
    }
}
