<?php

namespace App\Support;

use App\Models\User;
use Laravel\Fortify\Fortify;

final class RecoveryCodeManager
{
    /**
     * @return list<string>
     */
    public function activeCodes(User $user): array
    {
        $user = $user->fresh() ?? $user;

        if (blank($user->two_factor_recovery_codes)) {
            return [];
        }

        return array_values(array_filter(
            $user->recoveryCodes(),
            fn (mixed $code): bool => is_string($code) && $code !== ''
        ));
    }

    public function consume(User $user, string $recoveryCode): bool
    {
        $codes = $this->activeCodes($user);
        $matched = false;

        $remainingCodes = array_values(array_filter(
            $codes,
            function (string $code) use ($recoveryCode, &$matched): bool {
                if (hash_equals($code, $recoveryCode)) {
                    $matched = true;

                    return false;
                }

                return true;
            }
        ));

        if (! $matched) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($remainingCodes)),
        ])->save();

        return true;
    }
}
