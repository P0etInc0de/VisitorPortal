<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\VisitorPortalPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoAuthModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_only_blocks_local_login_for_normal_users(): void
    {
        config(['sso.auth_mode' => 'sso_only']);

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_synced_user_role_does_not_get_break_glass_local_login(): void
    {
        config(['sso.auth_mode' => 'sso_only']);
        VisitorPortalPermissions::sync();

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_break_glass_user_can_login_locally_in_sso_only_mode(): void
    {
        config(['sso.auth_mode' => 'sso_only']);
        VisitorPortalPermissions::sync();

        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->givePermissionTo('LoginLocallyInSsoOnlyMode');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('security.mfa.required', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_local_login_disabled_cannot_use_local_login(): void
    {
        config(['sso.auth_mode' => 'local']);

        $user = User::factory()->create(['local_login_allowed' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
