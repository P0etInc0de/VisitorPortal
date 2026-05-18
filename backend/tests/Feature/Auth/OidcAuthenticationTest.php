<?php

namespace Tests\Feature\Auth;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\Support\FakeOidcAuthenticator;
use Tests\Support\PermissionHelper;
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

    public function test_sso_admin_default_allows_overview_but_requires_app_mfa_for_admin_panel(): void
    {
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('overview'))->assertOk();
        $this->get('/admin')->assertRedirect(route('security.mfa.required'));
    }

    public function test_sso_admin_redirects_to_mfa_setup_when_sso_login_requires_app_mfa(): void
    {
        config(['security.mfa.app_required_for_auth_methods' => ['sso']]);
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('security.mfa.required'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_sso_admin_with_confirmed_mfa_can_access_admin_panel(): void
    {
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();
        $this->confirmMfa($admin);

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this->get('/admin')->assertOk();
    }

    public function test_sso_admin_is_not_blocked_in_admin_panel_when_admin_panel_policy_is_local_only(): void
    {
        config(['security.mfa.app_required_for_admin_panel_auth_methods' => ['local']]);
        $this->configureSso();
        $this->adminWithSsoIdentity();

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this->get('/admin')->assertOk();
    }

    private function configureSso(): void
    {
        config([
            'sso.enabled' => true,
            'sso.auth_mode' => 'local_and_sso',
            'sso.oidc.provisioning_mode' => 'disabled',
        ]);
    }

    private function adminWithSsoIdentity(): User
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        UserIdentity::query()->create([
            'user_id' => $admin->id,
            'provider' => 'oidc',
            'provider_user_id' => 'subject-123',
            'issuer' => 'https://idp.example.test',
            'subject' => 'subject-123',
        ]);

        $identity = new OidcIdentity(
            issuer: 'https://idp.example.test',
            subject: 'subject-123',
            email: $admin->email,
            emailVerified: true,
            displayName: $admin->full_name,
            groups: [],
            claims: [],
        );

        $this->app->bind(OidcAuthenticator::class, fn () => new FakeOidcAuthenticator($identity));

        return $admin;
    }

    private function confirmMfa(User $user): void
    {
        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();
    }
}
