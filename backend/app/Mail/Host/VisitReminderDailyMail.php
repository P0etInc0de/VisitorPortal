<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Mail\Host;

use App\Models\User;
use App\Models\Visit;
use App\Support\MailGreeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VisitReminderDailyMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    /**
     * @param  Collection<Visit>  $visitCollection
     */
    public function __construct(
        protected Collection $visitCollection,
        protected Collection $visitorCollection,
        protected User $user,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Heutige Besuche',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return (new Content(
            markdown: 'mail.host.visitReminderDaily',
        ))
            ->with('visitCollection', $this->visitCollection)
            ->with('visitorCollection', $this->visitorCollection)
            ->with('greeting', MailGreeting::forUser($this->user))
            ->with('user', $this->user);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
