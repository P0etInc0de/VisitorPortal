<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Auth;

use App\Auth\Sso\Contracts\OidcAuthenticator;
use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use Mockery;
use PragmaRX\Google2FA\Google2FA;
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
        $expectedSubjectHash = hash_hmac('sha256', 'subject-123', (string) config('app.key'));

        Log::shouldReceive('channel')->with('web')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')
            ->with('sso_login_success', Mockery::on(
                fn (array $context): bool => $context['user_id'] === $user->id
                    && $context['issuer'] === 'https://idp.example.test'
                    && $context['subject_hash'] === $expectedSubjectHash
                    && $context['subject_hash'] !== hash('sha256', 'subject-123')
                    && ! str_contains(json_encode($context), 'subject-123')
            ))
            ->once();

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('sso', session('auth.method'));
        $this->assertSame('https://idp.example.test', session('auth.oidc.issuer'));
        $this->assertSame('subject-123', session('auth.oidc.subject'));
    }

    public function test_sso_receptionist_default_redirects_to_reception_dashboard(): void
    {
        $this->configureSso();
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $this->bindSsoIdentityFor($receptionist);

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('reception.dashboard', absolute: false));

        $this->assertAuthenticatedAs($receptionist);
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

    public function test_sso_admin_default_can_complete_admin_panel_mfa_onboarding(): void
    {
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this
            ->get('/admin')
            ->assertRedirect(route('security.mfa.required'))
            ->assertSessionHas('security.mfa.auth_method', 'sso')
            ->assertSessionHas('security.mfa.intended_url', url('/admin'));

        $this->completeRequiredMfaOnboarding($admin, url('/admin'));

        $this->get('/admin')->assertOk();
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

    public function test_sso_login_requires_app_mfa_challenge_when_mfa_is_already_configured(): void
    {
        config(['security.mfa.app_required_for_auth_methods' => ['sso']]);
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();
        $this->confirmMfa($admin);

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('security.mfa.challenge'));

        $this->assertAuthenticatedAs($admin);

        $this
            ->post(route('security.mfa.challenge.verify'), [
                'code' => $this->currentTotp($admin),
            ])
            ->assertRedirect(url('/overview'))
            ->assertSessionHas('auth.app_mfa_satisfied_for_auth_method', 'sso');
    }

    public function test_sso_only_admin_with_local_login_disabled_can_complete_required_app_mfa_onboarding(): void
    {
        config(['security.mfa.app_required_for_auth_methods' => ['sso']]);
        $this->configureSso('sso_only');
        $admin = $this->adminWithSsoIdentity(['local_login_allowed' => false]);

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('security.mfa.required'))
            ->assertSessionHas('security.mfa.auth_method', 'sso');

        $this->assertAuthenticatedAs($admin);

        $this->completeRequiredMfaOnboarding($admin, url('/overview'));
    }

    public function test_sso_admin_with_confirmed_mfa_must_complete_app_mfa_challenge_for_admin_panel(): void
    {
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();
        $this->confirmMfa($admin);

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this
            ->get('/admin')
            ->assertRedirect(route('security.mfa.challenge'));

        $this
            ->post(route('security.mfa.challenge.verify'), [
                'code' => $this->currentTotp($admin),
            ])
            ->assertRedirect(url('/admin'))
            ->assertSessionHas('auth.app_mfa_satisfied_for_auth_method', 'sso');

        $this->get('/admin')->assertOk();
    }

    public function test_sso_admin_panel_recovery_code_challenge_is_one_time_use(): void
    {
        $this->configureSso();
        $admin = $this->adminWithSsoIdentity();
        $this->confirmMfa($admin);
        $recoveryCodes = $admin->fresh()->recoveryCodes();
        $usedRecoveryCode = $recoveryCodes[0];
        $otherRecoveryCode = $recoveryCodes[1];

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this
            ->get('/admin')
            ->assertRedirect(route('security.mfa.challenge'));

        $this
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertRedirect(url('/admin'))
            ->assertSessionHas('auth.app_mfa_satisfied_for_auth_method', 'sso')
            ->assertSessionHas('auth.app_mfa_satisfied_method', 'recovery_code');

        $activeCodes = $admin->fresh()->recoveryCodes();

        $this->assertNotContains($usedRecoveryCode, $activeCodes);
        $this->assertContains($otherRecoveryCode, $activeCodes);
        $this->assertCount(count($recoveryCodes) - 1, $activeCodes);

        $this->post('/logout');

        $this->get(route('auth.oidc.callback'))
            ->assertRedirect(route('overview', absolute: false));

        $this
            ->get('/admin')
            ->assertRedirect(route('security.mfa.challenge'));

        $this
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertSessionHasErrors('recovery_code');

        $this->assertContains($otherRecoveryCode, $admin->fresh()->recoveryCodes());

        $this
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $otherRecoveryCode,
            ])
            ->assertRedirect(url('/admin'));

        $this->assertNotContains($otherRecoveryCode, $admin->fresh()->recoveryCodes());
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

    private function configureSso(string $authMode = 'local_and_sso'): void
    {
        config([
            'sso.enabled' => true,
            'sso.auth_mode' => $authMode,
            'sso.oidc.provisioning_mode' => 'disabled',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function adminWithSsoIdentity(array $attributes = []): User
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        if ($attributes !== []) {
            $admin->forceFill($attributes)->save();
            $admin->refresh();
        }

        $this->bindSsoIdentityFor($admin);

        return $admin;
    }

    private function bindSsoIdentityFor(User $user): void
    {
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
    }

    private function confirmMfa(User $user): void
    {
        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();
    }

    private function completeRequiredMfaOnboarding(User $user, string $expectedRedirectUrl): void
    {
        $this
            ->get(route('security.mfa.setup'))
            ->assertOk()
            ->assertSee(__('Bitte Scanne den QR-Code mit deiner Authenticator-App, gebe den sechsstelligen Code ein und bestätige.'))
            ->assertSee('name="code"', false);

        $user->refresh();

        $this
            ->post(route('security.mfa.confirm'), [
                'code' => $this->currentTotp($user),
            ])
            ->assertRedirect(route('security.mfa.recovery-codes'));

        $user->refresh();

        $this->assertTrue($user->hasConfirmedTwoFactorAuthentication());

        $this
            ->get(route('security.mfa.recovery-codes'))
            ->assertOk()
            ->assertSee($user->recoveryCodes()[0]);

        $this
            ->post(route('security.mfa.continue'))
            ->assertRedirect($expectedRedirectUrl);
    }

    private function currentTotp(User $user): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }
}
