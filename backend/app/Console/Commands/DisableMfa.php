<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DisableMfa extends Command
{
    protected $signature = 'visitorportal:disable-mfa
        {email : User e-mail address}
        {--force : Disable MFA without interactive confirmation}';

    protected $description = 'Disable MFA for a user without exposing secrets.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('No user with this e-mail address was found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("This will disable MFA for {$user->email}. Continue?", false)) {
            $this->warn('No changes were made.');

            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Log::channel('web')->warning('security_app_mfa_disabled', [
            'user_id' => $user->id,
            'source' => 'cli',
        ]);

        $this->info("MFA disabled for {$user->email}.");

        return self::SUCCESS;
    }
}
