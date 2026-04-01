<?php

namespace App\Tasks;


use App\Models\Visit;
use App\Notifications\Host\VisitReminderDaily;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DailyVisitorReminder
{
    /*
     * Notify employees if they have visitors today
     * Shall run at 7am every day
     * Might be extended to notify the visitor as well...
     * */
    /**
     * @throws \Throwable
     */
    public function __invoke(): void
    {
        Log::info('DailyVisitorReminder started', [
            'date' => Carbon::today()->toDateString(),
        ]);


        $visitsOfToday = Visit::query()
            ->whereDate('scheduled_from', Carbon::today())
            ->with('responsibleUser')
            ->get();

        $visitsOfUsers = $visitsOfToday->groupBy('responsible_user_id');

        foreach($visitsOfUsers as $visitCollection)
        {
            $user = $visitCollection->first()->responsibleUser;

            Log::info('Sending Notification from DailyVisitReminder', [
                'user_id' => $user->id,
                'visit_count' => $visitCollection->count(),
            ]);

            Notification::send($user, new VisitReminderDaily($visitCollection, $user));
        }
    }
}
