<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Notifications\Guest;

use App\Mail\Guest\GuestCheckedInMail;
use App\Models\Visit;
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
        protected Visit $visit,
        protected ?string $signature = null,
    ) {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(Visitor $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(Visitor $notifiable): Mailable
    {
        Log::channel('mail')->info('Notifying Visitor per E-Mail, that the check-in was completed', [
            'visitor_id' => $notifiable->id,
            'visit_id' => $this->visit->id,
            'class' => get_class(),
        ]);

        return (new GuestCheckedInMail($this->visit, $notifiable, $this->signature))->to($notifiable->email);
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
