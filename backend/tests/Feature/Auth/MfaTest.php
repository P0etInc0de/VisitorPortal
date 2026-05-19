<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\SensitiveActionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
use Mockery;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_without_mfa_can_login_when_mfa_is_optional(): void
    {
        $user = (new PermissionHelper)->getUser();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('overview', absolute: false));
    }

    public function test_user_can_enable_confirm_view_recovery_codes_and_disable_mfa(): void
    {
        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.confirm'), [
                'code' => $this->currentTotp($user),
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->hasConfirmedTwoFactorAuthentication());

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.recovery-codes'))
            ->assertOk()
            ->assertJsonCount(8);

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('two-factor.disable'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_admin_without_mfa_is_redirected_to_security_setup_after_login(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('security.mfa.required'));
        $response->assertSessionHas('security.mfa.onboarding_required', true);
    }

    public function test_required_mfa_onboarding_can_be_completed_without_password_confirmation(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('security.mfa.required'));

        $this
            ->get(route('security.mfa.setup'))
            ->assertOk()
            ->assertSee(__('Einrichtung starten'));

        $this
            ->post(route('security.mfa.enable'))
            ->assertRedirect(route('security.mfa.setup'));

        $admin->refresh();

        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);

        $this
            ->post(route('security.mfa.confirm'), [
                'code' => $this->currentTotp($admin),
            ])
            ->assertRedirect(route('security.mfa.recovery-codes'));

        $admin->refresh();

        $this->assertTrue($admin->hasConfirmedTwoFactorAuthentication());

        $recoveryCode = $admin->recoveryCodes()[0];

        $this
            ->get(route('security.mfa.recovery-codes'))
            ->assertOk()
            ->assertSee($recoveryCode)
            ->assertSee(__('Weiter'));

        $this
            ->post(route('security.mfa.continue'))
            ->assertRedirect(url('/overview'));
    }

    public function test_admin_with_unconfirmed_mfa_remains_limited_to_setup_routes(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->refresh();

        $this
            ->actingAs($admin)
            ->get('/overview')
            ->assertRedirect(route('security.mfa.required'));

        $this
            ->actingAs($admin)
            ->get(route('profile.security'))
            ->assertRedirect(route('security.mfa.required'));

        $this
            ->actingAs($admin)
            ->withSession($this->mfaOnboardingSession())
            ->get(route('security.mfa.required'))
            ->assertOk();
    }

    public function test_required_mfa_page_does_not_render_normal_app_navigation(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this
            ->actingAs($admin)
            ->withSession($this->mfaOnboardingSession())
            ->get(route('security.mfa.required'))
            ->assertOk()
            ->assertSee(__('Zwei-Faktor-Authentifizierung erforderlich'))
            ->assertSee(__('Abmelden'))
            ->assertDontSee(__('Übersicht'))
            ->assertDontSee(__('Admin-Bereich'));
    }

    public function test_normal_user_cannot_access_required_mfa_onboarding_page_without_onboarding(): void
    {
        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->get(route('security.mfa.required'))
            ->assertRedirect(route('overview'));
    }

    public function test_unconfirmed_mfa_qr_code_requires_password_confirmation(): void
    {
        $user = (new PermissionHelper)->getUser();

        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $this
            ->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee(__('Einrichtung fortsetzen'))
            ->assertDontSee(__('Authenticator-App verbinden'));

        $this
            ->actingAs($user)
            ->get(route('profile.security.two-factor-setup'))
            ->assertRedirect(route('password.confirm', absolute: false));

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.security.two-factor-setup'))
            ->assertOk()
            ->assertSee(__('Authenticator-App verbinden'));
    }

    public function test_admin_with_confirmed_mfa_gets_two_factor_challenge_and_can_login_with_totp(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();

        $this->post(route('two-factor.login'), [
            'code' => $this->currentTotp($admin),
        ])->assertRedirect(route('overview', absolute: false))
            ->assertSessionHas('auth.app_mfa_satisfied_for_auth_method', 'local')
            ->assertSessionHas('auth.app_mfa_satisfied_method', 'totp');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_login_with_recovery_code_once(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $recoveryCode = $admin->fresh()->recoveryCodes()[0];

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login'), [
            'recovery_code' => $recoveryCode,
        ])->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotContains($recoveryCode, $admin->fresh()->recoveryCodes());
    }

    public function test_recovery_codes_are_only_visible_after_fresh_step_up(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);

        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this
            ->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee(__('Recovery Codes anzeigen'))
            ->assertDontSee($recoveryCode);

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'code' => $this->currentTotp($user),
            ])
            ->assertRedirect(route('profile.security.recovery-codes'));

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'))
            ->assertOk()
            ->assertSee($recoveryCode);
    }

    public function test_recovery_code_cannot_be_used_to_view_existing_recovery_codes(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'recovery_code' => $recoveryCode,
            ])
            ->assertSessionHasErrors('recovery_code');

        $this->assertContains($recoveryCode, $user->fresh()->recoveryCodes());
    }

    public function test_recovery_codes_view_step_up_expires_after_ten_minutes(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);

        $this
            ->actingAs($user)
            ->withSession([
                'security.step_up.confirmed_at' => now()->subSeconds(601)->timestamp,
                'security.step_up.method' => 'totp',
                'security.step_up.confirmed_for' => SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            ])
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW));
    }

    public function test_recovery_code_step_up_purposes_do_not_unlock_each_other(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);

        $this
            ->actingAs($user)
            ->withSession([
                'security.step_up.confirmed_at' => now()->timestamp,
                'security.step_up.method' => 'totp',
                'security.step_up.confirmed_for' => SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            ])
            ->post(route('profile.security.recovery-codes.regenerate'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE));

        $this
            ->actingAs($user)
            ->withSession([
                'security.step_up.confirmed_at' => now()->timestamp,
                'security.step_up.method' => 'totp',
                'security.step_up.confirmed_for' => SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE,
            ])
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW));
    }

    public function test_recovery_codes_can_be_regenerated_after_totp_step_up(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $oldCodes = $user->fresh()->recoveryCodes();

        $this
            ->actingAs($user)
            ->post(route('profile.security.recovery-codes.regenerate'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE), [
                'code' => $this->currentTotp($user),
            ])
            ->assertRedirect(route('profile.security.recovery-codes'));

        $newCodes = $user->fresh()->recoveryCodes();

        $this->assertNotSame($oldCodes, $newCodes);

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'))
            ->assertOk()
            ->assertSee($newCodes[0]);
    }

    public function test_recovery_codes_can_be_regenerated_after_recovery_code_step_up(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $usedRecoveryCode = $user->fresh()->recoveryCodes()[0];

        $this
            ->actingAs($user)
            ->post(route('profile.security.recovery-codes.regenerate'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertRedirect(route('profile.security.recovery-codes'));

        $this->assertNotContains($usedRecoveryCode, $user->fresh()->recoveryCodes());
    }

    public function test_recovery_code_security_events_are_logged_without_codes_or_secrets(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $recoveryCode = $user->fresh()->recoveryCodes()[0];
        $secret = (string) $user->fresh()->two_factor_secret;

        Log::shouldReceive('channel')->with('web')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')
            ->with('security_app_mfa_step_up_completed', Mockery::on(
                fn (array $context): bool => $context['user_id'] === $user->id
                    && $context['step_up_purpose'] === SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW
                    && $context['step_up_method'] === 'totp'
                    && ! str_contains(json_encode($context), $recoveryCode)
                    && ! str_contains(json_encode($context), $secret)
            ))
            ->once();
        Log::shouldReceive('info')
            ->with('security_recovery_codes_viewed', Mockery::on(
                fn (array $context): bool => $context['user_id'] === $user->id
                    && $context['step_up_purpose'] === SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW
                    && ! str_contains(json_encode($context), $recoveryCode)
                    && ! str_contains(json_encode($context), $secret)
            ))
            ->once();

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'));

        $this
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'code' => $this->currentTotp($user),
            ]);

        $this->get(route('profile.security.recovery-codes'))->assertOk();
    }

    public function test_sso_only_user_can_view_recovery_codes_after_app_mfa_step_up(): void
    {
        $user = (new PermissionHelper)->getUser();
        $user->forceFill(['local_login_allowed' => false])->save();
        $this->confirmMfa($user);

        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this
            ->actingAs($user)
            ->withSession(['auth.method' => 'sso'])
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'code' => $this->currentTotp($user),
            ])
            ->assertRedirect(route('profile.security.recovery-codes'));

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'))
            ->assertOk()
            ->assertSee($recoveryCode);
    }

    public function test_sso_only_user_does_not_see_local_password_or_optional_mfa_actions(): void
    {
        $user = (new PermissionHelper)->getUser();
        $user->forceFill(['local_login_allowed' => false])->save();

        $this
            ->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('Dieses Konto wird über SSO verwaltet. Das lokale Passwort kann hier nicht geändert werden.'))
            ->assertDontSee('update_password_current_password');

        $this
            ->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee(__('Optionale Zwei-Faktor-Aktivierung über das Profil ist für dieses Konto nicht verfügbar.'))
            ->assertDontSee(__('Zwei-Faktor-Authentifizierung aktivieren'));
    }

    public function test_two_factor_challenge_is_rate_limited(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('two-factor.login'), [
                'code' => '000000',
            ])->assertStatus(302);
        }

        $this->post(route('two-factor.login'), [
            'code' => '000000',
        ])->assertStatus(429);
    }

    public function test_admin_without_mfa_cannot_access_filament_admin_panel(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('security.mfa.required'));
    }

    public function test_admin_with_confirmed_mfa_requires_app_mfa_session_for_filament_admin_panel(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertRedirect(route('security.mfa.challenge'));
    }

    public function test_admin_with_satisfied_app_mfa_can_access_filament_admin_panel(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaSatisfiedSession())
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_panel_app_mfa_uses_auth_method_configuration(): void
    {
        config([
            'security.mfa.app_required_for_auth_methods' => [],
            'security.mfa.app_required_for_admin_panel_auth_methods' => ['sso'],
        ]);

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this
            ->actingAs($admin)
            ->withSession(['auth.method' => 'local'])
            ->get('/admin')
            ->assertOk();

        $this
            ->actingAs($admin)
            ->withSession(['auth.method' => 'sso'])
            ->get('/admin')
            ->assertRedirect(route('security.mfa.required'));
    }

    public function test_required_mfa_confirmation_redirects_to_intended_url(): void
    {
        config(['security.mfa.app_required_for_auth_methods' => []]);

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this
            ->actingAs($admin)
            ->withSession(['auth.method' => 'local'])
            ->get('/admin')
            ->assertRedirect(route('security.mfa.required'))
            ->assertSessionHas('security.mfa.intended_url');

        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->refresh();

        $this
            ->post(route('security.mfa.confirm'), [
                'code' => $this->currentTotp($admin),
            ])
            ->assertRedirect(route('security.mfa.recovery-codes'));

        $this->assertTrue($admin->fresh()->hasConfirmedTwoFactorAuthentication());

        $this
            ->post(route('security.mfa.continue'))
            ->assertRedirect(url('/admin'));
    }

    public function test_external_mfa_challenge_intended_url_falls_back_to_overview(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession([
                'auth.method' => 'local',
                'security.mfa.challenge_auth_method' => 'local',
                'security.mfa.challenge_intended_url' => 'https://evil.example.test/admin',
            ])
            ->post(route('security.mfa.challenge.verify'), [
                'code' => $this->currentTotp($admin),
            ])
            ->assertRedirect(route('overview'));
    }

    public function test_required_roles_configuration_is_respected(): void
    {
        config(['security.mfa.app_required_roles' => ['receptionist']]);

        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $this->post('/login', [
            'email' => $receptionist->email,
            'password' => 'password',
        ])->assertRedirect(route('security.mfa.required'));
    }

    public function test_optional_mfa_can_be_disabled_for_non_required_users(): void
    {
        config(['security.mfa.optional_for_users' => false]);

        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertDontSee(__('MFA aktivieren'));

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.enable'))
            ->assertForbidden();
    }

    public function test_required_mfa_user_cannot_disable_mfa_directly(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('two-factor.disable'))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasConfirmedTwoFactorAuthentication());
    }

    public function test_mfa_enforcement_can_be_disabled(): void
    {
        config(['security.mfa.enabled' => false]);

        $admin = (new PermissionHelper)->getSuperAdminUser();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('overview', absolute: false));
    }

    public function test_existing_confirmed_mfa_still_challenges_when_enforcement_is_disabled(): void
    {
        config(['security.mfa.enabled' => false]);

        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->assertGuest();
    }

    private function confirmMfa(User $user): void
    {
        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function mfaOnboardingSession(string $authMethod = 'local', ?string $intendedUrl = null): array
    {
        return [
            'auth.method' => $authMethod,
            'security.mfa.onboarding_required' => true,
            'security.mfa.authenticated_at' => now()->timestamp,
            'security.mfa.auth_method' => $authMethod,
            'security.mfa.intended_url' => $intendedUrl ?? url('/overview'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appMfaSatisfiedSession(string $authMethod = 'local'): array
    {
        return [
            'auth.method' => $authMethod,
            'auth.app_mfa_satisfied_at' => now()->timestamp,
            'auth.app_mfa_satisfied_method' => 'totp',
            'auth.app_mfa_satisfied_for_auth_method' => $authMethod,
        ];
    }

    private function currentTotp(User $user): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }
}
