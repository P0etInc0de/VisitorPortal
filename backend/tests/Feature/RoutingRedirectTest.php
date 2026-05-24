<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class RoutingRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_home_redirects_authenticated_user_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('overview'));
    }

    public function test_home_redirects_receptionist_to_reception_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $response = $this->actingAs($receptionist)->get('/');

        $response->assertRedirect(route('reception.dashboard'));
    }

    public function test_home_redirects_admin_to_overview(): void
    {
        config(['security.mfa.app_required_roles' => []]);

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:Visit'], 'admin');

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect(route('overview'));
    }
}
