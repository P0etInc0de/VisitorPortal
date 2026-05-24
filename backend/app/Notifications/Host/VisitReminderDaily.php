<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
        protected Collection $visitorCollection,
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
        Log::channel('mail')->info('Notifying User from VisitReminderDaily', [
            'user_id' => $notifiable->id,
            'visit_count' => $this->visitCollection->count(),
            'class' => get_class(),
        ]);

        return (new VisitReminderDailyMail($this->visitCollection, $this->visitorCollection, $notifiable))
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
