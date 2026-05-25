<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Auth;

use App\Models\Monitor;
use App\Models\Site;
use App\Models\User;
use App\Support\UserPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_uses_browser_locale_when_available(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
        ])->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('Anmelden');
    }

    public function test_login_screen_falls_back_to_english_for_unknown_browser_locale(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'zz-ZZ,xx;q=0.9',
        ])->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('Login');
    }

    public function test_login_screen_supports_french_browser_locale(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
        ])->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('Connexion');
    }

    public function test_login_screen_supports_czech_browser_locale(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'cs-CZ,cs;q=0.9,en;q=0.8',
        ])->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('Přihlásit se');
    }

    public function test_guest_layout_uses_theme_cookie(): void
    {
        $response = $this
            ->withCookie(UserPreferences::THEME_COOKIE, 'dark')
            ->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('data-theme="dark"', false);
    }

    public function test_guest_layout_uses_true_black_theme_cookie(): void
    {
        $response = $this
            ->withCookie(UserPreferences::THEME_COOKIE, 'true-black')
            ->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('data-theme="true-black"', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'locale' => 'de',
            'theme_preference' => 'dark',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response
            ->assertRedirect(route('overview', absolute: false))
            ->assertCookie(UserPreferences::LOCALE_COOKIE, 'de')
            ->assertCookie(UserPreferences::THEME_COOKIE, 'dark');
    }

    public function test_receptionist_without_intended_url_is_redirected_to_reception_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $this->post('/login', [
            'email' => $receptionist->email,
            'password' => 'password',
        ])->assertRedirect(route('reception.dashboard', absolute: false));

        $this->assertAuthenticatedAs($receptionist);
    }

    public function test_admin_without_intended_url_is_redirected_to_overview(): void
    {
        config(['security.mfa.app_required_roles' => []]);

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:Visit'], 'admin');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_manager_and_user_without_intended_url_are_redirected_to_overview(): void
    {
        foreach ([(new PermissionHelper)->getManagerUser(), (new PermissionHelper)->getUser()] as $user) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect(route('overview', absolute: false));

            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_admin_stale_reception_intended_url_falls_back_to_overview(): void
    {
        config(['security.mfa.app_required_roles' => []]);

        $admin = (new PermissionHelper)->getIndividualUser(['ViewAny:Visit'], 'admin');

        $this
            ->withSession(['url.intended' => route('reception.dashboard')])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ])->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_welcome_monitor_user_without_site_monitor_does_not_fall_back_to_other_site(): void
    {
        $site = Site::factory()->create(['name' => 'Site A']);
        $otherSite = Site::factory()->create(['name' => 'Site B']);
        $user = (new PermissionHelper)->getWelcomeMonitorUser();
        $user->forceFill(['site_id' => $site->id])->save();
        Monitor::query()->create([
            'site_id' => $otherSite->id,
            'name' => 'Other Site Monitor',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('monitors.missing', absolute: false));
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
