<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;
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
        $response->assertRedirect(route('profile.security'));
    }

    public function test_admin_with_unconfirmed_mfa_remains_limited_to_setup_routes(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();

        app(EnableTwoFactorAuthentication::class)($admin);
        $admin->refresh();

        $this
            ->actingAs($admin)
            ->get('/overview')
            ->assertRedirect(route('profile.security'));

        $this
            ->actingAs($admin)
            ->get(route('profile.security'))
            ->assertOk();
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
        ])->assertRedirect(route('overview', absolute: false));

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

    public function test_recovery_codes_are_only_visible_after_password_confirmation(): void
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
            ->assertRedirect(route('password.confirm', absolute: false));

        $this
            ->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.security.recovery-codes'))
            ->assertOk()
            ->assertSee($recoveryCode);
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
            ->assertRedirect(route('profile.security'));
    }

    public function test_admin_with_confirmed_mfa_can_access_filament_admin_panel(): void
    {
        $admin = (new PermissionHelper)->getSuperAdminUser();
        $this->confirmMfa($admin);

        $this
            ->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_required_roles_configuration_is_respected(): void
    {
        config(['security.mfa.required_roles' => ['receptionist']]);

        $receptionist = (new PermissionHelper)->getReceptionistUser();

        $this->post('/login', [
            'email' => $receptionist->email,
            'password' => 'password',
        ])->assertRedirect(route('profile.security'));
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

    private function confirmMfa(User $user): void
    {
        app(EnableTwoFactorAuthentication::class)($user);
        $user->refresh();

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();
    }

    private function currentTotp(User $user): string
    {
        $secret = Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret);

        return app(Google2FA::class)->getCurrentOtp($secret);
    }
}
