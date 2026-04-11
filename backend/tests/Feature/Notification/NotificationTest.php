<?php

namespace Tests\Feature\Notification;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Notifications\Host\GuestCheckedIn;
use App\Notifications\Host\VisitCreated;
use App\Notifications\Host\VisitReminderDaily;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_guest_checked_in_notification()
    {
        $user = User::factory()->create();
        $visitor = Visitor::factory()->create();

        // Send the notification
        Notification::send($user, new GuestCheckedIn($user, $visitor));

        // Extract the array of sent messages from Laravel's underlying Symfony Mailer
        $messages = app('mailer')->getSymfonyTransport()->messages();

        // See message
        // dd($messages);

        // Assert the array is not empty (an email was generated)
        $this->assertCount(1, $messages);

        // Grab the first email from the array
        $email = $messages[0];

        // Check the recipient
        $this->assertEquals($user->email, $email->getOriginalMessage()->getTo()[0]->getAddress());

        // Check the subject
        $this->assertEquals('Gast eingecheckt', $email->getOriginalMessage()->getSubject());

        // Check if the visitor's name is actually inside the rendered email body
        $this->assertStringContainsString(
            $visitor->first_name,
            $email->getOriginalMessage()->getBody()->bodyToString()
        );
    }

    public function test_daily_notification()
    {
        $user = User::factory()->create();
        $visit = Visit::factory()->create();
        $visits = collect([$visit]);
        $visitors = $visits->pluck('visitors', 'id');

        // Send the notification
        Notification::send($user, new VisitReminderDaily($visits, $visitors));

        // Extract the array of sent messages from Laravel's underlying Symfony Mailer
        $messages = app('mailer')->getSymfonyTransport()->messages();

        // See message
        // dd($messages);

        // Assert the array is not empty (an email was generated)
        $this->assertCount(1, $messages);

        // Grab the first E-Mail from the array
        $email = $messages[0];

        // Check the recipient
        $this->assertEquals($user->email, $email->getOriginalMessage()->getTo()[0]->getAddress());

        // Check the subject
        $this->assertEquals('Heutige Besuche', $email->getOriginalMessage()->getSubject());

        // Check if the visitor's name is actually inside the rendered email body
        $this->assertStringContainsString(
            Carbon::parse($visit->scheduled_until)->format('H:i'),
            $email->getOriginalMessage()->getBody()->bodyToString()
        );
    }

    public function test_visit_created_notification_host()
    {
        $user = User::factory()->create();
        $visit = Visit::factory()->create();
        $visitor1 = Visitor::factory()->create();
        $visitor2 = Visitor::factory()->create();
        $visitors = collect([$visitor1, $visitor2]);

        Notification::send($user, new VisitCreated($visit, $visitors));

        $messages = app('mailer')->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        $email = $messages[0];

        $this->assertEquals($user->email, $email->getOriginalMessage()->getTo()[0]->getAddress());

        $this->assertEquals('Besuch erfolgreich erstellt', $email->getOriginalMessage()->getSubject());

        $this->assertStringContainsString(
            Carbon::parse($visit->scheduled_until)->format('H:i'),
            $email->getOriginalMessage()->getBody()->bodyToString()
        );
    }

    public function test_visit_created_notification_guest()
    {
        $visit = Visit::factory()->create();
        $visitor = Visitor::factory()->create();

        Notification::send($visitor, new \App\Notifications\Guest\VisitCreated($visit));

        $messages = app('mailer')->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        $email = $messages[0];

        $this->assertEquals($visitor->email, $email->getOriginalMessage()->getTo()[0]->getAddress());

        $this->assertEquals('Ihr Besuch wurde registriert', $email->getOriginalMessage()->getSubject());

        $this->assertStringContainsString(
            Carbon::parse($visit->scheduled_until)->format('H:i'),
            $email->getOriginalMessage()->getBody()->bodyToString()
        );
    }
}
