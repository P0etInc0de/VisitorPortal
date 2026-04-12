<?php

namespace App\Notifications\Host;

use App\Mail\Host\GuestCheckedInMail;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class GuestCheckedIn extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected Visitor $visitor
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
        Log::info('Notifying User that GuestCheckedIn', [
            'user_id' => $notifiable->id,
            'visitor_id' => $this->visitor->id,
            'class' => get_class(),
        ]);

        return (new GuestCheckedInMail($notifiable, $this->visitor))->to($notifiable->email);
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
