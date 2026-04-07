<?php

namespace App\Mail\Host;

use App\Models\User;
use App\Models\Visit;
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

    public string $last_name;

    public string $gender;

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
    ) {
        $nameParts = explode(' ', $user->name);
        $this->last_name = end($nameParts);
        $this->gender = $user->gender;
    }

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
            ->with('user', $this->user)
            ->with('last_name', $this->last_name)
            ->with('gender', $this->gender);
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
