<?php
/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Notifications\Host;

use App\Mail\Host\GuestCheckedInMail;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Support\PortalNotificationData;
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
        protected Visitor $visitor
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
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
        return PortalNotificationData::make(
            type: 'guest_checked_in',
            titleKey: 'Besuch eingetroffen',
            messageKey: 'Gast :name ist soeben eingetroffen.',
            messageReplacements: [
                'name' => $this->visitorDisplayName(),
            ],
            actionUrl: route('portal.visits.show', $this->visit->getKey(), absolute: false),
            actionLabelKey: 'Weitere Informationen',
            context: [
                'visit_id' => $this->visit->id,
                'visitor_id' => $this->visitor->id,
                'visitor_name' => $this->visitorDisplayName(),
            ],
        );
    }

    private function visitorDisplayName(): string
    {
        $fullName = trim(implode(' ', array_filter([
            $this->visitor->first_name,
            $this->visitor->name,
        ])));

        if ($fullName !== '') {
            return $fullName;
        }

        foreach ([$this->visitor->company, $this->visitor->email] as $fallback) {
            $fallback = trim((string) $fallback);

            if ($fallback !== '') {
                return $fallback;
            }
        }

        return __('Gast');
    }
}
