<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

final readonly class AppMfaChallenge
{
    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    public function verify(Request $request): string
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        if ($request->filled('recovery_code')) {
            $recoveryCode = collect($user->recoveryCodes())
                ->first(fn (string $code): bool => hash_equals($code, (string) $request->input('recovery_code')));

            if ($recoveryCode) {
                $user->replaceRecoveryCode($recoveryCode);

                return 'recovery_code';
            }
        }

        if ($request->filled('code') && $this->provider->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            (string) $request->input('code')
        )) {
            return 'totp';
        }

        $key = $request->filled('recovery_code') && ! $request->filled('code')
            ? 'recovery_code'
            : 'code';

        throw ValidationException::withMessages([
            $key => [__('The provided two factor authentication code was invalid.')],
        ]);
    }
}
