<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Kiosk;

use App\Filament\Resources\Kiosks\Pages\CreateKiosk;
use App\Filament\Resources\Kiosks\Pages\ListKiosks;
use App\Models\Kiosk;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class KioskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_kiosk_through_the_filament_form(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $site = Site::default();
        $host = User::factory()->create(['site_id' => $site->id]);

        Livewire::actingAs($admin)
            ->test(CreateKiosk::class)
            ->fillForm([
                'name' => 'Empfang Test',
                'site_id' => $site->id,
                'walk_in_host_user_id' => $host->id,
                'is_active' => true,
                'allow_walk_in' => true,
                'allow_check_out' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('kiosks', [
            'name' => 'Empfang Test',
            'site_id' => $site->id,
            'walk_in_host_user_id' => $host->id,
        ]);

        $kiosk = Kiosk::query()->where('name', 'Empfang Test')->sole();
        $this->assertNotEmpty($kiosk->token);
        $this->assertStringContainsString('/kiosk/'.$kiosk->token, $kiosk->url());
    }

    public function test_admin_can_open_the_kiosk_list(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        Kiosk::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListKiosks::class)
            ->assertOk();
    }

    public function test_admin_can_manage_kiosks(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this->assertTrue($admin->can('viewAny', Kiosk::class));
        $this->assertTrue($admin->can('create', Kiosk::class));
        $this->assertTrue($admin->can('update', Kiosk::factory()->create()));
    }

    public function test_kiosk_manager_can_manage_kiosks(): void
    {
        $manager = (new PermissionHelper)->getKioskManagerUser();

        $this->assertTrue($manager->can('viewAny', Kiosk::class));
        $this->assertTrue($manager->can('create', Kiosk::class));
    }

    public function test_receptionist_can_view_but_not_create_kiosks(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $this->assertTrue($receptionist->can('viewAny', Kiosk::class));
        $this->assertFalse($receptionist->can('create', Kiosk::class));
    }

    public function test_regular_user_cannot_view_kiosks(): void
    {
        $user = (new PermissionHelper)->getUserUser();

        $this->assertFalse($user->can('viewAny', Kiosk::class));
    }

    public function test_regenerating_the_token_invalidates_the_old_url(): void
    {
        $kiosk = Kiosk::factory()->create();
        $oldToken = $kiosk->token;

        $kiosk->regenerateToken();

        $this->assertNotSame($oldToken, $kiosk->fresh()->token);
    }
}
