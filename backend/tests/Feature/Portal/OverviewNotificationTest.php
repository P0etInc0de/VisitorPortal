<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Portal;

use App\Livewire\Portal\StatusNotificationsCard;
use App\Models\Visit;
use App\Models\Visitor;
use App\Notifications\Host\GuestCheckedInDatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class OverviewNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_shows_guest_check_in_notifications(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Mira',
            'name' => 'Sample',
        ]);

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $this->withSession(['locale' => 'de'])
            ->actingAs($user)
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('Status & Benachrichtigungen')
            ->assertSee('Gast Mira Sample ist soeben eingetroffen.');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $notification = $user->notifications()->firstOrFail();

        Livewire::actingAs($user)
            ->test(StatusNotificationsCard::class)
            ->call('toggleRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_toggle_notification_back_to_unread(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $notification = $user->notifications()->firstOrFail();
        $notification->markAsRead();

        Livewire::actingAs($user)
            ->test(StatusNotificationsCard::class)
            ->call('toggleRead', $notification->id);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_delete_notification(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $notification = $user->notifications()->firstOrFail();
        $message = __('Gast :name ist soeben eingetroffen.', [
            'name' => trim(($visitor->first_name ?? '').' '.($visitor->name ?? '')),
        ]);

        Livewire::actingAs($user)
            ->test(StatusNotificationsCard::class)
            ->assertSee($message)
            ->call('deleteNotification', $notification->id)
            ->assertDontSee($message);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    public function test_overview_translates_notifications_using_active_locale(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Lisa',
            'name' => 'Punctual',
        ]);

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $this->withSession(['locale' => 'en'])
            ->actingAs($user)
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('Status & Notifications')
            ->assertSee('Visit arrived')
            ->assertSee('Guest Lisa Punctual has just arrived.')
            ->assertSee('More information');
    }

    public function test_status_notifications_card_polls_while_visible(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();

        $this->actingAs($user)
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('wire:poll.10s.visible', false);
    }

    public function test_status_notifications_card_shows_new_database_notification_after_refresh(): void
    {
        app()->setLocale('de');

        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'host_user_id' => $user->id,
            'substitute_user_id' => null,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Nora',
            'name' => 'Polling',
        ]);
        $message = 'Gast Nora Polling ist soeben eingetroffen.';

        $component = Livewire::actingAs($user)
            ->test(StatusNotificationsCard::class)
            ->assertDontSee($message);

        Notification::send($user, new GuestCheckedInDatabaseNotification($visit, $visitor));

        $component
            ->call('$refresh')
            ->assertSee($message);
    }
}
