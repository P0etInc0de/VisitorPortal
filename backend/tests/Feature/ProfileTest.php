<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
            ->assertSee('🇫🇷 Français', false)
            ->assertSee('🇨🇿 Čeština', false)
            ->assertSee(__('True Black (OLED)'))
            ->assertDontSee(__('Konto löschen'));
    }

    public function test_profile_settings_can_be_updated(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'locale' => 'de',
                'theme_preference' => 'dark',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertCookie(UserPreferences::LOCALE_COOKIE, 'de')
            ->assertCookie(UserPreferences::THEME_COOKIE, 'dark');

        $user->refresh();

        $this->assertSame('de', $user->locale);
        $this->assertSame('dark', $user->theme_preference);
    }

    public function test_account_fields_cannot_be_updated_from_profile_settings(): void
    {
        $user = (new PermissionHelper)->getUser();
        $originalName = $user->name;
        $originalEmail = $user->email;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'locale' => 'en',
                'theme_preference' => 'system',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame($originalName, $user->name);
        $this->assertSame($originalEmail, $user->email);
        $this->assertNotNull($user->email_verified_at);
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

    public function test_french_and_czech_locales_are_supported(): void
    {
        $user = (new PermissionHelper)->getUser();

        foreach (['fr', 'cs'] as $locale) {
            $this
                ->actingAs($user)
                ->patch('/profile', [
                    'locale' => $locale,
                    'theme_preference' => 'system',
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect('/profile')
                ->assertCookie(UserPreferences::LOCALE_COOKIE, $locale);

            $this->assertSame($locale, $user->fresh()->locale);
        }
    }

    public function test_theme_preference_can_be_updated_via_sync_endpoint(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this
            ->actingAs($user)
            ->patchJson(route('profile.theme-preference.update'), [
                'theme_preference' => 'true-black',
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'theme_preference' => 'true-black',
            ])
            ->assertCookie(UserPreferences::THEME_COOKIE, 'true-black');

        $this->assertSame('true-black', $user->fresh()->theme_preference);
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
