<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\User;
use Illuminate\Support\Str;

final class SsoProfileSync
{
    public function sync(User $user, OidcIdentity $identity): void
    {
        $updates = [];

        $firstName = $this->stringClaim($identity, 'given_name');
        $lastName = $this->stringClaim($identity, 'family_name');

        if ($firstName !== null && $firstName !== '') {
            $updates['first_name'] = $firstName;
        }

        if ($lastName !== null && $lastName !== '') {
            $updates['name'] = $lastName;
        }

        if ($identity->email !== null && $identity->emailVerified && ! $this->emailBelongsToAnotherUser($user, $identity->email)) {
            $updates['email'] = Str::lower($identity->email);
            $updates['email_verified_at'] = $user->email === Str::lower($identity->email)
                ? $user->email_verified_at
                : now();
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }
    }

    private function stringClaim(OidcIdentity $identity, string $claim): ?string
    {
        $value = $identity->claims[$claim] ?? null;

        return is_string($value) ? trim($value) : null;
    }

    private function emailBelongsToAnotherUser(User $user, string $email): bool
    {
        return User::query()
            ->where('email', Str::lower($email))
            ->whereKeyNot($user->getKey())
            ->exists();
    }
}
