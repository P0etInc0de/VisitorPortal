<?php

namespace App\Tasks;


use App\Models\User;
use App\Notifications\Guest\VisitCreated;
use App\Notifications\Host\GuestCheckedIn;
use App\Notifications\Host\SingleVisitReminder;
use App\Notifications\Host\VisitReminderDaily;
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
        //Testing
        Notification::send(User::first(), new VisitCreated());
        Notification::send(User::first(), new \App\Notifications\Host\VisitCreated());
        Notification::send(User::first(), new SingleVisitReminder());
        Notification::send(User::first(), new GuestCheckedIn());
        Notification::send(User::first(), new VisitReminderDaily());
    }
}
