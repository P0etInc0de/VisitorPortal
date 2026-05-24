<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\RecoveryCodeManager;
use App\Support\SensitiveActionConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Mockery;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    private const MFA_SAMPLE_CODES = [
        'Beispiel: 123 456',
        '123 456',
        '123456',
    ];

    private const ROBUST_TOTP_INPUT_MARKUP = [
        'data-totp-code-input',
        'name="code"',
        'h-12',
        'rounded-xl',
        'border-base-300',
        'bg-base-100',
        'text-base-content',
        'focus:border-primary',
        'focus:ring-2',
        'focus:ring-primary/20',
    ];

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
            ->withSession([
                'security.step_up.confirmed_at' => now()->timestamp,
                'security.step_up.method' => 'totp',
                'security.step_up.confirmed_for' => SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            ])
            ->get(route('profile.security.recovery-codes'))
            ->assertOk()
            ->assertSee($user->fresh()->recoveryCodes()[0]);

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

    public function test_fortify_recovery_code_routes_do_not_expose_or_regenerate_codes(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $codes = $user->fresh()->recoveryCodes();

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.recovery-codes'))
            ->assertForbidden()
            ->assertDontSee($codes[0]);

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.regenerate-recovery-codes'))
            ->assertForbidden();

        $this->assertSame($codes, $user->fresh()->recoveryCodes());
    }

    public function test_fortify_recovery_code_routes_are_blocked_when_mfa_enforcement_is_disabled(): void
    {
        config(['security.mfa.enabled' => false]);

        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $codes = $user->fresh()->recoveryCodes();

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('two-factor.recovery-codes'))
            ->assertForbidden()
            ->assertDontSee($codes[0]);

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('two-factor.regenerate-recovery-codes'))
            ->assertForbidden();

        $this->assertSame($codes, $user->fresh()->recoveryCodes());
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
            ->assertSee('max-w-[34rem]', false)
            ->assertSee('whitespace-nowrap', false)
            ->assertSee('justify-self-center', false)
            ->assertSee('[&_svg]:max-w-full', false)
            ->assertSee('w-full max-w-sm', false)
            ->assertSee(__('Bitte Scanne den QR-Code mit deiner Authenticator-App, gebe den sechsstelligen Code ein und bestätige.'))
            ->assertSee('name="code"', false)
            ->assertDontSee(__('Authenticator-App vorbereiten'));

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

        $recoveryCodesResponse = $this->get(route('security.mfa.recovery-codes'));

        $this->assertSensitiveNoStoreHeaders($recoveryCodesResponse);

        $recoveryCodesResponse
            ->assertOk()
            ->assertSee('pb-2 pt-6 text-center', false)
            ->assertSee('mt-5 border-t border-base-300/70', false)
            ->assertDontSee('border-b border-base-300/70', false)
            ->assertDontSee(__('Recovery Codes speichern'))
            ->assertSee(__('Zwei-Faktor-Authentifizierung wurde aktiviert. Speichere jetzt deine Recovery Codes. Bewahre diese Codes sicher auf!'))
            ->assertSee(__('Jeder Code kann nur einmal verwendet werden.'))
            ->assertSee('alert alert-success rounded-2xl', false)
            ->assertSee($recoveryCode)
            ->assertSee(__('Weiter'))
            ->assertSee("window.addEventListener('pageshow'", false)
            ->assertSee('event.persisted', false);

        $this
            ->post(route('security.mfa.continue'))
            ->assertRedirect(url('/overview'));

        $this
            ->get(route('security.mfa.recovery-codes'))
            ->assertRedirect(url('/overview'))
            ->assertDontSee($recoveryCode);
    }

    public function test_mfa_pages_render_robust_totp_code_input_without_sample_codes(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $loginUser = (new PermissionHelper)->getUser();
        $this->confirmMfa($loginUser);

        $profileSetupUser = (new PermissionHelper)->getUser();
        app(EnableTwoFactorAuthentication::class)($profileSetupUser);
        $profileSetupUser->refresh();

        $requiredSetupUser = (new PermissionHelper)->getSuperAdminUser();

        $this->post('/login', [
            'email' => $loginUser->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $responses = [
            'Login-MFA-Challenge' => $this->get(route('two-factor.login')),
            'App-MFA-Challenge' => $this
                ->actingAs($admin)
                ->withSession($this->appMfaChallengeSession())
                ->get(route('security.mfa.challenge')),
            'Adminpanel-/SSO-App-MFA-Challenge' => $this
                ->actingAs($admin)
                ->withSession($this->appMfaChallengeSession('sso', url('/admin')))
                ->get(route('security.mfa.challenge')),
            'Recovery-Code-Step-up' => $this
                ->actingAs($admin)
                ->get(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW)),
            'Profil-MFA-Setup' => $this
                ->actingAs($profileSetupUser)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get(route('profile.security.two-factor-setup')),
            'Pflicht-MFA-Setup' => $this
                ->actingAs($requiredSetupUser)
                ->withSession($this->mfaOnboardingSession())
                ->get(route('security.mfa.setup')),
        ];

        foreach ($responses as $page => $response) {
            $this->assertMfaResponseHasRobustTotpInput($response, $page);
        }

        foreach (['Profil-MFA-Setup', 'Pflicht-MFA-Setup'] as $page) {
            $responses[$page]
                ->assertSee('justify-self-center', false)
                ->assertSee('[&_svg]:max-w-full', false);
        }
    }

    public function test_recovery_code_input_is_hidden_behind_disclosure_on_mfa_challenges(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $loginUser = (new PermissionHelper)->getUser();
        $this->confirmMfa($loginUser);

        $this->post('/login', [
            'email' => $loginUser->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $responses = [
            $this->get(route('two-factor.login')),
            $this
                ->actingAs($admin)
                ->withSession($this->appMfaChallengeSession())
                ->get(route('security.mfa.challenge')),
        ];

        foreach ($responses as $response) {
            $response
                ->assertOk()
                ->assertSee('data-testid="mfa-recovery-toggle"', false)
                ->assertSee('<details', false)
                ->assertSee('Recovery Code', false)
                ->assertDontSee('Alternativ kannst du einen Recovery Code verwenden.')
                ->assertDontSee('oder einem Recovery Code');
        }
    }

    public function test_mfa_error_messages_are_translated_in_german_ui(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $admin->forceFill(['locale' => 'de'])->save();
        $this->confirmMfa($admin);

        $this
            ->withCookie('locale', 'de')
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('two-factor.login'));

        $this
            ->withCookie('locale', 'de')
            ->post(route('two-factor.login'), [
                'code' => '000000',
            ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHasErrors([
                'code' => 'Der eingegebene Zwei-Faktor-Code ist ungültig.',
            ]);

        $this
            ->withCookie('locale', 'de')
            ->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('Der eingegebene Zwei-Faktor-Code ist ungültig.')
            ->assertDontSee('The provided two factor authentication code was invalid.');

        $this
            ->withCookie('locale', 'de')
            ->post(route('two-factor.login'), [
                'recovery_code' => 'not-a-valid-recovery-code',
            ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHasErrors([
                'recovery_code' => 'Der eingegebene Recovery Code ist ungültig.',
            ]);

        $this
            ->withCookie('locale', 'de')
            ->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee('Der eingegebene Recovery Code ist ungültig.')
            ->assertDontSee('The provided two factor authentication code was invalid.')
            ->assertDontSee('The provided two factor recovery code was invalid.');
    }

    public function test_mfa_challenge_passes_grouped_totp_to_provider_normalized(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $capturedCode = null;

        $this->mock(TwoFactorAuthenticationProvider::class, function ($mock) use (&$capturedCode): void {
            $mock
                ->shouldReceive('verify')
                ->once()
                ->with(Mockery::type('string'), Mockery::on(function ($code) use (&$capturedCode): bool {
                    $capturedCode = $code;

                    return true;
                }))
                ->andReturnTrue();
        });

        $this
            ->actingAs($user)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'code' => '123 456',
            ])
            ->assertRedirect(route('overview'));

        $this->assertSame('123456', $capturedCode);
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

    public function test_receptionist_with_confirmed_mfa_login_redirects_to_reception_dashboard(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $this->confirmMfa($receptionist);

        $this->post('/login', [
            'email' => $receptionist->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login'), [
            'code' => $this->currentTotp($receptionist),
        ])->assertRedirect(route('reception.dashboard', absolute: false))
            ->assertSessionHas('auth.app_mfa_satisfied_for_auth_method', 'local')
            ->assertSessionHas('auth.app_mfa_satisfied_method', 'totp');

        $this->assertAuthenticatedAs($receptionist);
    }

    public function test_login_totp_accepts_grouped_code_with_space(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this
            ->post(route('two-factor.login'), [
                'code' => $this->groupTotp($this->currentTotp($admin)),
            ])
            ->assertRedirect(route('overview', absolute: false))
            ->assertSessionHas('auth.app_mfa_satisfied_method', 'totp');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_login_with_recovery_code_once(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $recoveryCodes = $admin->fresh()->recoveryCodes();
        $recoveryCode = $recoveryCodes[0];
        $otherRecoveryCode = $recoveryCodes[1];

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->post(route('two-factor.login'), [
            'recovery_code' => $recoveryCode,
        ])->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotContains($recoveryCode, $admin->fresh()->recoveryCodes());
        $this->assertContains($otherRecoveryCode, $admin->fresh()->recoveryCodes());
        $this->assertCount(count($recoveryCodes) - 1, $admin->fresh()->recoveryCodes());

        $this->post('/logout');

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this
            ->post(route('two-factor.login'), [
                'recovery_code' => $recoveryCode,
            ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHasErrors('recovery_code');

        $this->assertGuest();

        $this->post(route('two-factor.login'), [
            'recovery_code' => $otherRecoveryCode,
        ])->assertRedirect(route('overview', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotContains($otherRecoveryCode, $admin->fresh()->recoveryCodes());
    }

    public function test_login_totp_with_extra_invalid_recovery_code_logs_totp_only(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        Log::shouldReceive('channel')->with('web')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')
            ->with('security_app_mfa_step_up_completed', Mockery::on(
                fn (array $context): bool => $context['user_id'] === $admin->id
                    && $context['step_up_purpose'] === 'login'
                    && $context['step_up_method'] === 'totp'
            ))
            ->once();
        Log::shouldReceive('info')
            ->with('security_recovery_code_used', Mockery::any())
            ->never();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this
            ->post(route('two-factor.login'), [
                'code' => $this->currentTotp($admin),
                'recovery_code' => 'not-a-valid-recovery-code',
            ])
            ->assertRedirect(route('overview', absolute: false))
            ->assertSessionHas('auth.app_mfa_satisfied_method', 'totp');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_failed_login_mfa_with_totp_and_recovery_code_logs_mixed_attempt_method(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);
        $invalidTotp = 'abc123';
        $invalidRecoveryCode = 'not-a-valid-recovery-code';
        $secret = (string) $admin->fresh()->two_factor_secret;

        Log::shouldReceive('channel')->with('web')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')
            ->with('security_app_mfa_challenge_failed', Mockery::on(
                fn (array $context): bool => $context['user_id'] === $admin->id
                    && $context['step_up_purpose'] === 'login'
                    && $context['attempted_method'] === 'mixed'
                    && ! str_contains(json_encode($context), $invalidTotp)
                    && ! str_contains(json_encode($context), $invalidRecoveryCode)
                    && ! str_contains(json_encode($context), $secret)
            ))
            ->once();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this
            ->post(route('two-factor.login'), [
                'code' => $invalidTotp,
                'recovery_code' => $invalidRecoveryCode,
            ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHasErrors('recovery_code');

        $this->assertGuest();
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

        $profileRecoveryCodesResponse = $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'));

        $this->assertSensitiveNoStoreHeaders($profileRecoveryCodesResponse);

        $profileRecoveryCodesResponse
            ->assertOk()
            ->assertSee($recoveryCode)
            ->assertSee("window.addEventListener('pageshow'", false)
            ->assertSee('event.persisted', false);

        $repeatedProfileRecoveryCodesResponse = $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'));

        $this->assertSensitiveNoStoreHeaders($repeatedProfileRecoveryCodesResponse);

        $repeatedProfileRecoveryCodesResponse
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW))
            ->assertDontSee($recoveryCode);
    }

    public function test_step_up_totp_accepts_multiple_whitespace(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'code' => $this->groupTotpWithMultipleWhitespace($this->currentTotp($user)),
            ])
            ->assertRedirect(route('profile.security.recovery-codes'));
    }

    public function test_consumed_recovery_codes_are_hidden_from_recovery_code_page(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $recoveryCodes = $user->fresh()->recoveryCodes();
        $usedRecoveryCode = $recoveryCodes[0];
        $remainingRecoveryCode = $recoveryCodes[1];

        $this
            ->actingAs($user)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertRedirect(route('overview'));

        $activeCodes = $user->fresh()->recoveryCodes();

        $this->assertNotContains($usedRecoveryCode, $activeCodes);
        $this->assertContains($remainingRecoveryCode, $activeCodes);
        $this->assertCount(count($recoveryCodes) - 1, $activeCodes);

        $this
            ->actingAs($user)
            ->withSession([
                'security.step_up.confirmed_at' => now()->timestamp,
                'security.step_up.method' => 'totp',
                'security.step_up.confirmed_for' => SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
            ])
            ->get(route('profile.security.recovery-codes'))
            ->assertOk()
            ->assertSee(__('Diese Anzeige wurde durch eine frische Sicherheitsbestätigung freigeschaltet und ist nur kurzzeitig verfügbar.'))
            ->assertDontSee($usedRecoveryCode)
            ->assertSee($remainingRecoveryCode);
    }

    public function test_recovery_code_manager_consumes_code_once_with_locked_reload(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $recoveryCodes = $user->fresh()->recoveryCodes();
        $usedRecoveryCode = $recoveryCodes[0];
        $remainingRecoveryCode = $recoveryCodes[1];
        $manager = app(RecoveryCodeManager::class);

        $this->assertTrue($manager->consume($user, $usedRecoveryCode));
        $this->assertFalse($manager->consume($user, $usedRecoveryCode));

        $activeCodes = $user->fresh()->recoveryCodes();

        $this->assertNotContains($usedRecoveryCode, $activeCodes);
        $this->assertContains($remainingRecoveryCode, $activeCodes);
        $this->assertCount(count($recoveryCodes) - 1, $activeCodes);
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

    public function test_recovery_code_view_rejects_recovery_code_even_with_valid_totp(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'code' => $this->currentTotp($user),
                'recovery_code' => $recoveryCode,
            ])
            ->assertSessionHasErrors('recovery_code');

        $this->assertContains($recoveryCode, $user->fresh()->recoveryCodes());
    }

    public function test_profile_recovery_codes_are_not_visible_after_recovery_code_step_up_method(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        $this
            ->actingAs($user)
            ->withSession([
                'security.recovery_codes.just_regenerated' => true,
                'security.recovery_codes.step_up_method' => 'recovery_code',
            ])
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW))
            ->assertDontSee($recoveryCode);
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

        $this
            ->actingAs($user)
            ->get(route('profile.security.recovery-codes'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW))
            ->assertDontSee($newCodes[0]);
    }

    public function test_recovery_codes_cannot_be_regenerated_after_recovery_code_step_up(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $oldRecoveryCodes = $user->fresh()->recoveryCodes();
        $usedRecoveryCode = $oldRecoveryCodes[0];

        $this
            ->actingAs($user)
            ->post(route('profile.security.recovery-codes.regenerate'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertSessionHasErrors('recovery_code');

        $this->assertSame($oldRecoveryCodes, $user->fresh()->recoveryCodes());
    }

    public function test_recovery_code_step_up_rejects_recovery_code_with_whitespace(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $oldRecoveryCodes = $user->fresh()->recoveryCodes();
        $usedRecoveryCode = $oldRecoveryCodes[0];

        $this
            ->actingAs($user)
            ->post(route('profile.security.recovery-codes.regenerate'))
            ->assertRedirect(route('security.step-up.show', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE));

        $this
            ->actingAs($user)
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_REGENERATE), [
                'recovery_code' => $this->spaceRecoveryCode($usedRecoveryCode),
            ])
            ->assertSessionHasErrors('recovery_code');

        $this->assertSame($oldRecoveryCodes, $user->fresh()->recoveryCodes());
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

    public function test_sso_admin_with_admin_panel_mfa_requirement_sees_context_explanation(): void
    {
        config([
            'security.mfa.app_required_for_auth_methods' => [],
            'security.mfa.app_required_for_admin_panel_auth_methods' => ['sso'],
        ]);

        $admin = (new PermissionHelper)->getSuperAdminUser();
        $admin->forceFill(['local_login_allowed' => false])->save();

        $this
            ->actingAs($admin)
            ->withSession(['auth.method' => 'sso'])
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee(__('Für den Adminbereich ist Zwei-Faktor-Authentifizierung erforderlich. Beim Zugriff auf den Adminbereich wirst du durch die Einrichtung geführt.'))
            ->assertSee(__('Dieses Konto wird über SSO verwaltet. Für den Zugriff auf den Adminbereich kann zusätzlich lokale VisitorPortal-Zwei-Faktor-Authentifizierung erforderlich sein.'))
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

    public function test_app_mfa_challenge_recovery_code_is_one_time_use_and_keeps_other_codes(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);
        $recoveryCodes = $admin->fresh()->recoveryCodes();
        $usedRecoveryCode = $recoveryCodes[0];
        $otherRecoveryCode = $recoveryCodes[1];

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertRedirect(route('overview'))
            ->assertSessionHas('auth.app_mfa_satisfied_method', 'recovery_code');

        $activeCodes = $admin->fresh()->recoveryCodes();

        $this->assertNotContains($usedRecoveryCode, $activeCodes);
        $this->assertContains($otherRecoveryCode, $activeCodes);
        $this->assertCount(count($recoveryCodes) - 1, $activeCodes);

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $usedRecoveryCode,
            ])
            ->assertSessionHasErrors('recovery_code');

        $this->assertContains($otherRecoveryCode, $admin->fresh()->recoveryCodes());

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'recovery_code' => $otherRecoveryCode,
            ])
            ->assertRedirect(route('overview'));

        $this->assertNotContains($otherRecoveryCode, $admin->fresh()->recoveryCodes());
    }

    public function test_app_mfa_challenge_rejects_totp_with_letters(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'code' => 'abc123',
            ])
            ->assertSessionHasErrors('code')
            ->assertSessionMissing('auth.app_mfa_satisfied_at');
    }

    public function test_app_mfa_challenge_rejects_totp_with_wrong_length(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaChallengeSession())
            ->post(route('security.mfa.challenge.verify'), [
                'code' => '12345',
            ])
            ->assertSessionHasErrors('code')
            ->assertSessionMissing('auth.app_mfa_satisfied_at');
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

    public function test_expired_app_mfa_session_requires_new_admin_panel_challenge(): void
    {
        config(['security.mfa.app_session_ttl_minutes' => 1]);

        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession($this->appMfaSatisfiedSession(satisfiedAt: now()->subSeconds(61)->timestamp))
            ->get('/admin')
            ->assertRedirect(route('security.mfa.challenge'))
            ->assertSessionMissing('auth.app_mfa_satisfied_at')
            ->assertSessionMissing('auth.app_mfa_satisfied_method')
            ->assertSessionMissing('auth.app_mfa_satisfied_for_auth_method');
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

    public function test_external_step_up_intended_url_falls_back_to_overview(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);

        $this
            ->actingAs($user)
            ->withSession([
                'security.step_up.purpose' => SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW,
                'security.step_up.intended_url' => 'https://evil.example.test/profile/security/recovery-codes',
            ])
            ->post(route('security.step-up.verify', SensitiveActionConfirmation::PURPOSE_RECOVERY_CODES_VIEW), [
                'code' => $this->currentTotp($user),
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

    public function test_required_mfa_onboarding_without_intended_url_uses_receptionist_default_landing(): void
    {
        config(['security.mfa.app_required_roles' => ['receptionist']]);

        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $this->post('/login', [
            'email' => $receptionist->email,
            'password' => 'password',
        ])->assertRedirect(route('security.mfa.required'));

        $this
            ->get(route('security.mfa.setup'))
            ->assertOk();

        $receptionist->refresh();

        $this
            ->post(route('security.mfa.confirm'), [
                'code' => $this->currentTotp($receptionist),
            ])
            ->assertRedirect(route('security.mfa.recovery-codes'));

        $this
            ->post(route('security.mfa.continue'))
            ->assertRedirect(url(route('reception.dashboard', absolute: false)));
    }

    public function test_app_mfa_challenge_without_intended_url_uses_receptionist_default_landing(): void
    {
        $receptionist = (new PermissionHelper)->getReceptionistUser();
        $this->confirmMfa($receptionist);

        $this
            ->actingAs($receptionist)
            ->withSession([
                'auth.method' => 'local',
                'security.mfa.challenge_auth_method' => 'local',
            ])
            ->post(route('security.mfa.challenge.verify'), [
                'code' => $this->currentTotp($receptionist),
            ])
            ->assertRedirect(url(route('reception.dashboard', absolute: false)));
    }

    public function test_optional_mfa_can_be_disabled_for_non_required_users(): void
    {
        config(['security.mfa.optional_for_users' => false]);

        $user = (new PermissionHelper)->getUser();

        $this
            ->actingAs($user)
            ->get(route('profile.security'))
            ->assertOk()
            ->assertDontSee(__('Zwei-Faktor-Authentifizierung aktivieren'));

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

    public function test_admin_panel_required_mfa_user_cannot_disable_mfa_even_when_login_mfa_is_not_required(): void
    {
        config([
            'security.mfa.app_required_for_auth_methods' => [],
            'security.mfa.app_required_for_admin_panel_auth_methods' => ['sso'],
        ]);

        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession([
                'auth.method' => 'local',
                'auth.password_confirmed_at' => time(),
            ])
            ->delete(route('two-factor.disable'))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasConfirmedTwoFactorAuthentication());
    }

    public function test_admin_panel_required_mfa_user_does_not_see_disable_button_in_profile(): void
    {
        config([
            'security.mfa.app_required_for_auth_methods' => [],
            'security.mfa.app_required_for_admin_panel_auth_methods' => ['sso'],
        ]);

        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->withSession(['auth.method' => 'local'])
            ->get(route('profile.security'))
            ->assertOk()
            ->assertSee(__('Zwei-Faktor-Authentifizierung kann für dieses Konto nicht deaktiviert werden, weil sie für mindestens einen Sicherheitskontext verpflichtend ist.'))
            ->assertDontSee('<form method="POST" action="'.route('two-factor.disable').'"', false);
    }

    public function test_optional_mfa_disable_logs_security_event_without_secrets(): void
    {
        $user = (new PermissionHelper)->getUser();
        $this->confirmMfa($user);
        $secret = (string) $user->fresh()->two_factor_secret;
        $recoveryCode = $user->fresh()->recoveryCodes()[0];

        Log::shouldReceive('channel')->with('web')->andReturnSelf()->byDefault();
        Log::shouldReceive('info')
            ->with('security_app_mfa_disabled', Mockery::on(
                fn (array $context): bool => $context['user_id'] === $user->id
                    && ! str_contains(json_encode($context), $secret)
                    && ! str_contains(json_encode($context), $recoveryCode)
            ))
            ->once();

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete(route('two-factor.disable'))
            ->assertRedirect();
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
    private function appMfaSatisfiedSession(string $authMethod = 'local', ?int $satisfiedAt = null): array
    {
        return [
            'auth.method' => $authMethod,
            'auth.app_mfa_satisfied_at' => $satisfiedAt ?? now()->timestamp,
            'auth.app_mfa_satisfied_method' => 'totp',
            'auth.app_mfa_satisfied_for_auth_method' => $authMethod,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appMfaChallengeSession(string $authMethod = 'local', ?string $intendedUrl = null): array
    {
        return [
            'auth.method' => $authMethod,
            'security.mfa.challenge_auth_method' => $authMethod,
            'security.mfa.challenge_intended_url' => $intendedUrl ?? route('overview'),
        ];
    }

    private function currentTotp(User $user): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    private function groupTotp(string $code): string
    {
        return substr($code, 0, 3).' '.substr($code, 3);
    }

    private function groupTotpWithMultipleWhitespace(string $code): string
    {
        return substr($code, 0, 2).'  '.substr($code, 2, 2)."\t".substr($code, 4);
    }

    private function spaceRecoveryCode(string $code): string
    {
        return substr($code, 0, 4).' '.substr($code, 4, 6).' - '.substr($code, 11, 3).' '.substr($code, 14);
    }

    private function assertMfaResponseHasRobustTotpInput(TestResponse $response, string $page): void
    {
        $status = $response->baseResponse->getStatusCode();

        $this->assertSame(
            200,
            $status,
            sprintf('%s returned HTTP %d%s', $page, $status, $response->headers->get('Location') ? ' and redirected to '.$response->headers->get('Location') : '')
        );

        foreach (self::ROBUST_TOTP_INPUT_MARKUP as $fragment) {
            $response->assertSee($fragment, false);
        }

        foreach (self::MFA_SAMPLE_CODES as $sampleCode) {
            $response->assertDontSee($sampleCode, false);
        }
    }

    private function assertSensitiveNoStoreHeaders(TestResponse $response): void
    {
        $cacheControl = (string) $response->headers->get('Cache-Control');

        foreach (['no-store', 'no-cache', 'private', 'max-age=0', 'must-revalidate'] as $directive) {
            $this->assertStringContainsString($directive, $cacheControl);
        }

        $this->assertStringNotContainsString('public', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
        $response->assertHeader('Expires', '0');
    }
}
