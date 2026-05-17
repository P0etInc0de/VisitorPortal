<?php

namespace Tests\Feature\Auth;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeOidcAuthenticator;
use Tests\TestCase;

class OidcAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sso_redirect_is_not_available_when_sso_is_disabled(): void
    {
        config(['sso.enabled' => false]);

        $this->get(route('auth.oidc.redirect'))->assertNotFound();
    }

    public function test_sso_redirect_is_not_available_in_local_mode(): void
    {
        config([
            'sso.enabled' => true,
            'sso.auth_mode' => 'local',
        ]);

        $this->get(route('auth.oidc.redirect'))->assertNotFound();
    }

    public function test_sso_redirect_is_available_in_local_and_sso_mode(): void
    {
        config([
            'sso.enabled' => true,
            'sso.auth_mode' => 'local_and_sso',
        ]);
        $this->app->bind(OidcAuthenticator::class, fn () => new FakeOidcAuthenticator);

        $this->get(route('auth.oidc.redirect'))->assertRedirect('/fake-oidc-provider');
    }

    public function test_callback_logs_user_in_and_sets_sso_session_context(): void
    {
        config([
            'sso.enabled' => true,
            'sso.auth_mode' => 'local_and_sso',
            'sso.oidc.provisioning_mode' => 'disabled',
        ]);

        $user = User::factory()->create();
        UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'oidc',
            'provider_user_id' => 'subject-123',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
        ]);

        $identity = new OidcIdentity(
            issuer: 'https://idp.example.test',
            subject: 'subject-123',
            email: $user->email,
            emailVerified: true,
            displayName: $user->full_name,
            groups: [],
            claims: [],
        );
        $this->app->bind(OidcAuthenticator::class, fn () => new FakeOidcAuthenticator($identity));

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('sso', session('auth.method'));
        $this->assertSame('https://idp.example.test', session('auth.oidc.issuer'));
        $this->assertSame('subject-123', session('auth.oidc.subject'));
    }

    public function test_disabled_user_cannot_login_with_valid_sso_identity(): void
    {
        config([
            'sso.enabled' => true,
            'sso.auth_mode' => 'local_and_sso',
            'sso.oidc.provisioning_mode' => 'disabled',
        ]);

        $user = User::factory()->create(['is_active' => false]);
        UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => 'oidc',
            'provider_user_id' => 'subject-123',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
        ]);
        $this->app->bind(OidcAuthenticator::class, fn () => new FakeOidcAuthenticator);

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
