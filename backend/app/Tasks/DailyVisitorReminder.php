<?php

namespace app\Tasks;


use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Notification;

class DailyVisitorReminder
{
    public function __invoke(): void
    {
        //Get Visits of today from Model
        //foreach visit, -> notify the host and the visitor

        //Testing
        Notification::send(User::all(), new TestNotification());

        // In the future, we need to use a queue
    }
}
