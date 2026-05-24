<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Fortify;

final class RecoveryCodeManager
{
    public function __construct(private readonly MfaCodeNormalizer $normalizer) {}

    /**
     * @return list<string>
     */
    public function activeCodes(User $user): array
    {
        $user = $user->fresh() ?? $user;

        if (blank($user->two_factor_recovery_codes)) {
            return [];
        }

        return $this->decodeActiveCodes($user);
    }

    public function consume(User $user, string $recoveryCode): bool
    {
        $normalizedRecoveryCode = $this->normalizer->normalizeRecoveryCode($recoveryCode);

        if ($normalizedRecoveryCode === null) {
            return false;
        }

        return DB::transaction(function () use ($user, $normalizedRecoveryCode): bool {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedUser) {
                return false;
            }

            $codes = $this->decodeActiveCodes($lockedUser);
            $matched = false;

            $remainingCodes = array_values(array_filter(
                $codes,
                function (string $code) use ($normalizedRecoveryCode, &$matched): bool {
                    if (hash_equals($code, $normalizedRecoveryCode)) {
                        $matched = true;

                        return false;
                    }

                    return true;
                }
            ));

            if (! $matched) {
                return false;
            }

            $lockedUser->forceFill([
                'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($remainingCodes, JSON_THROW_ON_ERROR)),
            ])->save();

            return true;
        });
    }

    /**
     * @return list<string>
     */
    private function decodeActiveCodes(User $user): array
    {
        if (blank($user->two_factor_recovery_codes)) {
            return [];
        }

        $codes = $user->recoveryCodes();

        if (! is_array($codes)) {
            return [];
        }

        return array_values(array_filter(
            $codes,
            fn (mixed $code): bool => is_string($code) && $code !== ''
        ));
    }
}
