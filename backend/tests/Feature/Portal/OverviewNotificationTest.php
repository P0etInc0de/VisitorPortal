<?php

namespace Tests\Feature\Portal;

use App\Livewire\Portal\StatusNotificationsCard;
use App\Models\Visit;
use App\Models\Visitor;
use App\Notifications\Host\GuestCheckedIn;
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
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Max',
            'name' => 'Mustermann',
        ]);

        Notification::send($user, new GuestCheckedIn($visit, $visitor));

        $this->actingAs($user)
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('Status & Benachrichtigungen')
            ->assertSee('Gast Max Mustermann ist soeben eingetroffen.');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();
        $visit = Visit::factory()->create([
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedIn($visit, $visitor));

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
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedIn($visit, $visitor));

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
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create();

        Notification::send($user, new GuestCheckedIn($visit, $visitor));

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
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
        ]);
        $visitor = Visitor::factory()->create([
            'first_name' => 'Lisa',
            'name' => 'Punctual',
        ]);

        Notification::send($user, new GuestCheckedIn($visit, $visitor));

        $this->withSession(['locale' => 'en'])
            ->actingAs($user)
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('Status & Notifications')
            ->assertSee('Visit arrived')
            ->assertSee('Guest Lisa Punctual has just arrived.')
            ->assertSee('More information');
    }
}
