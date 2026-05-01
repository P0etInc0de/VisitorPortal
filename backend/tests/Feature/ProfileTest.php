<?php

namespace Tests\Feature;

use App\Support\UserPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee(__('Sprache'))
            ->assertSee(__('Theme'))
            ->assertSee('🇩🇪 Deutsch', false)
            ->assertSee('🇬🇧 English', false)
            ->assertSee(__('True Black (OLED)'))
            ->assertDontSee(__('Konto löschen'));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'locale' => 'de',
                'theme_preference' => 'dark',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertCookie(UserPreferences::LOCALE_COOKIE, 'de')
            ->assertCookie(UserPreferences::THEME_COOKIE, 'dark');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('de', $user->locale);
        $this->assertSame('dark', $user->theme_preference);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'locale' => 'en',
                'theme_preference' => 'system',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_locale_from_database_overrides_browser_locale(): void
    {
        $user = (new PermissionHelper)->getUser();
        $user->update(['locale' => 'de']);

        $response = $this
            ->withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Sprache');
    }

    public function test_user_cannot_delete_their_own_account(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertForbidden();

        $this->assertNotNull($user->fresh());
    }

    public function test_delete_profile_route_is_forbidden_even_with_wrong_password(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertForbidden();

        $this->assertNotNull($user->fresh());
    }
}
