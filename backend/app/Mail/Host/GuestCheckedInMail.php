<?php

namespace App\Mail\Host;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestCheckedInMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $user_name;

    public string $user_gender;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected User $user,
        protected Visitor $visitor,
    ) {
        $nameParts = explode(' ', $user->name);
        $this->user_name = end($nameParts);
        $this->user_gender = $user->gender;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Gast eingecheckt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return (new Content(
            markdown: 'mail.host.guestCheckedIn',
        ))
            ->with('user_name', $this->user_name)
            ->with('user_gender', $this->user_gender)
            ->with('visitor', $this->visitor);
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
