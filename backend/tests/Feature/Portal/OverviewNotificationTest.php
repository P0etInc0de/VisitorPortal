<?php

namespace Tests\Feature\Portal;

use App\Models\Visit;
use App\Models\Visitor;
use App\Notifications\Host\GuestCheckedIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

        $this->actingAs($user)
            ->patch(route('portal.notifications.read', $notification->id))
            ->assertRedirect(route('overview'));

        $this->assertNotNull($notification->fresh()->read_at);
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

        $this->actingAs($user)
            ->delete(route('portal.notifications.destroy', $notification->id))
            ->assertRedirect(route('overview'));

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }
}
