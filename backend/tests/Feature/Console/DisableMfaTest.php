<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisableMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_disable_mfa_requires_confirmation(): void
    {
        $user = $this->userWithMfa();

        $this->artisan('visitorportal:disable-mfa', [
            'email' => $user->email,
        ])
            ->expectsConfirmation("This will disable MFA for {$user->email}. Continue?", 'no')
            ->expectsOutput('No changes were made.')
            ->assertExitCode(1);

        $this->assertTrue($user->fresh()->hasConfirmedTwoFactorAuthentication());
    }

    public function test_disable_mfa_can_be_confirmed_interactively(): void
    {
        $user = $this->userWithMfa();

        $this->artisan('visitorportal:disable-mfa', [
            'email' => $user->email,
        ])
            ->expectsConfirmation("This will disable MFA for {$user->email}. Continue?", 'yes')
            ->assertExitCode(0);

        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_recovery_codes);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_disable_mfa_force_skips_confirmation(): void
    {
        $user = $this->userWithMfa();

        $this->artisan('visitorportal:disable-mfa', [
            'email' => $user->email,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    private function userWithMfa(): User
    {
        return User::factory()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
