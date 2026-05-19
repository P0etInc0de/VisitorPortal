<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

final readonly class AppMfaChallenge
{
    public function __construct(
        private TwoFactorAuthenticationProvider $provider,
        private RecoveryCodeManager $recoveryCodes,
        private SecurityEventLogger $securityEvents,
    ) {}

    /**
     * @param  list<string>  $allowedMethods
     */
    public function verify(Request $request, array $allowedMethods = ['totp', 'recovery_code'], ?string $purpose = null): string
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $allowedMethods = array_values(array_intersect($allowedMethods, ['totp', 'recovery_code']));

        if ($request->filled('recovery_code')) {
            if (! in_array('recovery_code', $allowedMethods, true)) {
                $this->logFailure($request, $purpose, 'recovery_code');

                throw ValidationException::withMessages([
                    'recovery_code' => [__('Recovery Codes können für diese Sicherheitsbestätigung nicht verwendet werden.')],
                ]);
            }

            if ($this->recoveryCodes->consume($user, (string) $request->input('recovery_code'))) {
                $this->securityEvents->log($request, 'security_recovery_code_used', [
                    'step_up_purpose' => $purpose,
                    'step_up_method' => 'recovery_code',
                ]);

                return 'recovery_code';
            }
        }

        if (in_array('totp', $allowedMethods, true) && $request->filled('code') && $this->provider->verify(
            Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            (string) $request->input('code')
        )) {
            return 'totp';
        }

        $attemptedMethod = $request->filled('recovery_code') && ! $request->filled('code')
            ? 'recovery_code'
            : 'totp';

        $this->logFailure($request, $purpose, $attemptedMethod, $allowedMethods);

        $key = $attemptedMethod === 'recovery_code'
            ? 'recovery_code'
            : 'code';

        throw ValidationException::withMessages([
            $key => [__('The provided two factor authentication code was invalid.')],
        ]);
    }

    /**
     * @param  list<string>  $allowedMethods
     */
    private function logFailure(Request $request, ?string $purpose, string $attemptedMethod, array $allowedMethods = []): void
    {
        $this->securityEvents->log($request, 'security_app_mfa_challenge_failed', [
            'step_up_purpose' => $purpose,
            'attempted_method' => $attemptedMethod,
            'allowed_methods' => implode(',', $allowedMethods),
        ]);
    }
}
