<?php

namespace Tests\Feature;

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
            ->assertSee(__('Sprache ändern'))
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
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
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
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
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
