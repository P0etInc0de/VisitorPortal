<?php

namespace App\Mail\Host;

use App\Enums\GenderEnum;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class VisitCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $user_name;

    protected GenderEnum $user_gender;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected Visit $visit,
        protected Collection $visitors,
        protected User $user,
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
            subject: 'Besuch erfolgreich erstellt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return (new Content(
            markdown: 'mail.host.visitCreated',
        ))
            ->with('visitors', $this->visitors)
            ->with('user_name', $this->user_name)
            ->with('user_gender', $this->user_gender)
            ->with('visit', $this->visit);
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
