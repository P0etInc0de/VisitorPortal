<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_403_page_has_understandable_copy_and_post_logout(): void
    {
        Route::middleware('web')->get('/__test/forbidden-authenticated', fn () => abort(403));

        $user = User::factory()->create();

        $this
            ->withSession(['locale' => 'de'])
            ->actingAs($user)
            ->get('/__test/forbidden-authenticated')
            ->assertForbidden()
            ->assertSeeInOrder(['max-w-xl', '403', 'Zugriff verweigert'], false)
            ->assertSee('text-center', false)
            ->assertSee('Zugriff verweigert')
            ->assertSee('Du hast keine Berechtigung für diese Seite oder Aktion.')
            ->assertSee('Falls du der Meinung bist, dass du Zugriff benötigst, kontaktiere bitte einen Administrator.')
            ->assertSee('Zurück')
            ->assertSee('Abmelden')
            ->assertSee('method="POST" action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('method="GET" action="'.route('logout').'"', false);
    }

    public function test_logout_button_post_ends_authenticated_session(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_guest_403_page_links_to_login_without_logout_form(): void
    {
        Route::middleware('web')->get('/__test/forbidden-guest', fn () => abort(403));

        $this
            ->withSession(['locale' => 'de'])
            ->get('/__test/forbidden-guest')
            ->assertForbidden()
            ->assertSeeInOrder(['max-w-xl', '403', 'Zugriff verweigert'], false)
            ->assertSee('Zugriff verweigert')
            ->assertSee('Du hast keine Berechtigung für diese Seite.')
            ->assertSee('Zur Anmeldung')
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('Abmelden')
            ->assertDontSee('action="'.route('logout').'"', false);
    }
}
