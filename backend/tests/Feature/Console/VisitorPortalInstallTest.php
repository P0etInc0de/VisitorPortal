<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Console;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitorPortalInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_permissions_creates_production_safe_roles_and_permissions_idempotently(): void
    {
        $this->artisan('visitorportal:sync-permissions')->assertExitCode(0);
        $this->artisan('visitorportal:sync-permissions')->assertExitCode(0);

        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'receptionist', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'ManageAny:Site', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'ViewSite:Visit', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'LoginLocallyInSsoOnlyMode', 'guard_name' => 'web']);
        $this->assertSame(1, Role::query()->where('name', 'admin')->count());
        $this->assertSame(1, Permission::query()->where('name', 'ViewSite:Visit')->count());
        $receptionistRole = Role::query()->where('name', 'receptionist')->firstOrFail();
        $receptionistPermissions = $receptionistRole->permissions->pluck('name');

        $this->assertTrue($receptionistPermissions->contains('ViewSite:Visit'));
        $this->assertTrue($receptionistPermissions->contains('CancelSite:Visit'));
        $this->assertFalse($receptionistPermissions->contains('EditSite:Visit'));
        $this->assertFalse($receptionistPermissions->contains('DeleteSite:Visit'));
        $this->assertFalse($receptionistPermissions->contains('EditOwn:Visit'));
        $this->assertFalse($receptionistPermissions->contains('DeleteOwn:Visit'));
        $this->assertFalse($receptionistPermissions->contains('CancelOwn:Visit'));

        foreach (['admin', 'manager', 'receptionist', 'user'] as $roleName) {
            $this->assertFalse(
                Role::query()->where('name', $roleName)->firstOrFail()->hasPermissionTo('LoginLocallyInSsoOnlyMode'),
                "{$roleName} must not receive the break-glass local login permission by default."
            );
        }
    }

    public function test_create_admin_creates_active_admin_without_demo_password(): void
    {
        $this->artisan('visitorportal:create-admin', [
            '--email' => 'admin@example.org',
            '--first-name' => 'Ada',
            '--last-name' => 'Admin',
        ])
            ->expectsQuestion('Admin password', 'VerySecurePass123!')
            ->expectsQuestion('Confirm admin password', 'VerySecurePass123!')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'admin@example.org')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse(password_verify('ChangeMe-42!', $user->password));
    }

    public function test_create_admin_rejects_inactive_site(): void
    {
        $inactiveSite = Site::factory()->create(['is_active' => false]);

        $this->artisan('visitorportal:create-admin', [
            '--email' => 'admin@example.org',
            '--site-id' => $inactiveSite->id,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.org',
        ]);
    }
}
