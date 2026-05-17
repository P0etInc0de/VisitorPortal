<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DisableMfa extends Command
{
    protected $signature = 'visitorportal:disable-mfa {email : User e-mail address}';

    protected $description = 'Disable MFA for a user without exposing secrets.';

    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error('No user with this e-mail address was found.');

            return self::FAILURE;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        Log::channel('web')->warning('MFA disabled by CLI.', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->info("MFA disabled for {$user->email}.");

        return self::SUCCESS;
    }
}
