<?php

namespace app\Tasks;


use App\Models\User;
use App\Notifications\Guest\VisitCreated;
use App\Notifications\Host\GuestCheckedIn;
use App\Notifications\Host\SingleVisitReminder;
use App\Notifications\Host\VisitReminderDaily;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Notification;

class DailyVisitorReminder
{
    public function __invoke(): void
    {
        //Get Visits of today from Model
        //foreach visit, -> notify the host and the visitor

        //Testing
        Notification::send(User::first(), new TestNotification());
        Notification::send(User::first(), new VisitCreated());
        Notification::send(User::first(), new \App\Notifications\Host\VisitCreated());
        Notification::send(User::first(), new SingleVisitReminder());
        Notification::send(User::first(), new GuestCheckedIn());
        Notification::send(User::first(), new VisitReminderDaily());
        // In the future, we need to use a queue
    }
}
