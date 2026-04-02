<?php

namespace App\Notifications\Host;

use App\Mail\Host\VisitReminderDailyMail;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VisitReminderDaily extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    /**
     * @param  Collection<Visit>  $visitCollection
     */
    public function __construct(
        protected Collection $visitCollection,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): Mailable
    {
        Log::info('Notifying User from VisitReminderDaily', [
            'user_id' => $notifiable->id,
            'visit_count' => $this->visitCollection->count(),
            'class' => get_class(),
        ]);

        return (new VisitReminderDailyMail($this->visitCollection, $notifiable))
            ->to($notifiable->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
