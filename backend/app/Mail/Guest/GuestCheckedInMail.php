<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Mail\Guest;

use App\Models\Visit;
use App\Models\Visitor;
use App\Support\MailGreeting;
use App\Support\SignatureAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestCheckedInMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected Visit $visit,
        protected Visitor $visitor,
        protected ?string $signature = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihr Besuch wurde bestätigt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return (new Content(
            markdown: 'mail.guest.guestCheckedIn',
        ))
            ->with('greeting', MailGreeting::forVisitor($this->visitor))
            ->with('visitor', $this->visitor)
            ->with('visit', $this->visit)
            ->with('signature', $this->signature);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return SignatureAttachment::fromDataUrl($this->signature);
    }
}
