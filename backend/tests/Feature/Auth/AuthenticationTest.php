<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\UserPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'Accept-Language' => 'fr-FR,fr;q=0.9',
        ])->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('Login');
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

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
