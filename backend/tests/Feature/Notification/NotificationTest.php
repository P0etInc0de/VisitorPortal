<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Notification;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Notifications\Guest\SingleVisitReminder as GuestSingleVisitReminder;
use App\Notifications\Host\GuestCheckedInDatabaseNotification;
use App\Notifications\Host\GuestCheckedInMailNotification;
use App\Notifications\Host\VisitCreated;
use App\Notifications\Host\VisitReminderDaily;
use App\Tasks\DailyVisitorReminder;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_guest_checked_in_database_notification(): void
    {
        $user = User::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $this->assertCount(0, app('mailer')->getSymfonyTransport()->messages());

        $notification = $user->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('guest_checked_in', $notification->data['type']);
        $this->assertSame('Besuch eingetroffen', $notification->data['title_key']);
        $this->assertSame('Gast :name ist soeben eingetroffen.', $notification->data['message_key']);
        $this->assertSame(
            trim($visitor->first_name.' '.$visitor->name),
            $notification->data['message_replacements']['name']
        );
    }

    public function test_guest_checked_in_mail_notification(): void
    {
        $user = User::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedInMailNotification($visit, $visitor));

        $messages = app('mailer')->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        $email = $messages[0];

        $this->assertEquals($user->email, $email->getOriginalMessage()->getTo()[0]->getAddress());
        $this->assertEquals('Gast eingecheckt', $email->getOriginalMessage()->getSubject());
        $this->assertStringContainsString(
            $visitor->first_name,
            $email->getOriginalMessage()->getBody()->bodyToString()
        );
        $this->assertCount(0, $user->fresh()->notifications);
    }

    public function test_guest_checked_in_notifications_use_separate_channels(): void
    {
        $user = User::factory()->create();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create();

        $databaseNotification = new GuestCheckedInDatabaseNotification($visit, $visitor);
        $mailNotification = new GuestCheckedInMailNotification($visit, $visitor);

        $this->assertSame(['database'], $databaseNotification->via($user));
        $this->assertNotInstanceOf(ShouldQueue::class, $databaseNotification);

        $this->assertSame(['mail'], $mailNotification->via($user));
        $this->assertInstanceOf(ShouldQueue::class, $mailNotification);
        $this->assertTrue($mailNotification->afterCommit);
    }

    public function test_check_in_action_creates_database_notification_without_running_mail_queue(): void
    {
        Queue::fake();

        $host = User::factory()->create();
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $host->id,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Mira',
            'name' => 'Sample',
        ]);

        $visit->visitors()->attach($visitor->id);

        $this->actingAs($receptionist)
            ->post(route('reception.participants.check-in', [$visit, $visitor]))
            ->assertStatus(302);

        $notification = $host->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('guest_checked_in', $notification->data['type']);
        $this->assertSame('Gast :name ist soeben eingetroffen.', $notification->data['message_key']);
        $this->assertSame('Mira Sample', $notification->data['message_replacements']['name']);

        Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job): bool {
            return $job->notification instanceof GuestCheckedInMailNotification
                && $job->channels === ['mail']
                && $job->afterCommit === true;
        });
    }

    public function test_check_in_action_does_not_notify_inactive_host(): void
    {
        Notification::fake();

        $host = User::factory()->create(['is_active' => false]);
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $host->id,
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Mira',
            'name' => 'InactiveHost',
        ]);

        $visit->visitors()->attach($visitor->id);

        $this->actingAs($receptionist)
            ->post(route('reception.participants.check-in', [$visit, $visitor]))
            ->assertStatus(302);

        Notification::assertNotSentTo($host, GuestCheckedInDatabaseNotification::class);
        Notification::assertNotSentTo($host, GuestCheckedInMailNotification::class);
    }

    public function test_check_in_action_does_not_notify_substitute_automatically(): void
    {
        Notification::fake();

        $host = User::factory()->create();
        $substitute = User::factory()->create();
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $host->id,
            'substitute_user_id' => $substitute->id,
            'status' => VisitStatusEnum::Planned->value,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Mira',
            'name' => 'HostOnly',
        ]);

        $visit->visitors()->attach($visitor->id);

        $this->actingAs($receptionist)
            ->post(route('reception.participants.check-in', [$visit, $visitor]))
            ->assertStatus(302);

        Notification::assertSentTo($host, GuestCheckedInDatabaseNotification::class);
        Notification::assertSentTo($host, GuestCheckedInMailNotification::class);
        Notification::assertNotSentTo($substitute, GuestCheckedInDatabaseNotification::class);
        Notification::assertNotSentTo($substitute, GuestCheckedInMailNotification::class);
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

    public function test_daily_visitor_reminder_sends_to_active_hosts_only(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-05-17 07:00:00');

        try {
            $activeHost = User::factory()->create(['is_active' => true]);
            $inactiveHost = User::factory()->create(['is_active' => false]);
            $substituteUser = User::factory()->create(['is_active' => true]);
            $activeVisit = Visit::factory()->create([
                'host_user_id' => $activeHost->id,
                'substitute_user_id' => $substituteUser->id,
                'scheduled_from' => now()->copy()->setTime(10, 0),
                'scheduled_until' => now()->copy()->setTime(11, 0),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $inactiveVisit = Visit::factory()->create([
                'host_user_id' => $inactiveHost->id,
                'scheduled_from' => now()->copy()->setTime(10, 0),
                'scheduled_until' => now()->copy()->setTime(11, 0),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $activeVisit->visitors()->attach(Visitor::factory()->create()->id);
            $inactiveVisit->visitors()->attach(Visitor::factory()->create()->id);

            (new DailyVisitorReminder)();

            Notification::assertSentTo($activeHost, VisitReminderDaily::class);
            Notification::assertNotSentTo($inactiveHost, VisitReminderDaily::class);
            Notification::assertNotSentTo($substituteUser, VisitReminderDaily::class);
        } finally {
            Carbon::setTestNow();
        }
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
        config(['privacy.notice_url' => 'https://example.org/privacy']);

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
        $this->assertStringContainsString(
            'https://example.org/privacy',
            $email->getOriginalMessage()->getBody()->bodyToString()
        );
    }

    public function test_guest_single_visit_reminder_has_release_ready_copy()
    {
        config(['privacy.notice_url' => 'https://example.org/privacy']);

        $visitor = Visitor::factory()->create();

        Notification::send($visitor, new GuestSingleVisitReminder);

        $messages = app('mailer')->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        $body = $messages[0]->getOriginalMessage()->getBody()->bodyToString();

        $this->assertEquals('Erinnerung an Ihren Besuch', $messages[0]->getOriginalMessage()->getSubject());
        $this->assertStringContainsString('Erinnerung an Ihren Besuch', $body);
        $this->assertStringContainsString('https://example.org/privacy', $body);
        $this->assertStringNotContainsString('Introduction', $body);
        $this->assertStringNotContainsString('Button Text', $body);
    }
}
