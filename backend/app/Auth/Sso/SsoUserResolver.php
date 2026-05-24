<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\Site;
use App\Models\User;
use App\Models\UserIdentity;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class SsoUserResolver
{
    public function __construct(
        private readonly SsoRoleMapper $roleMapper,
        private readonly SsoProfileSync $profileSync,
    ) {}

    /**
     * @throws AuthenticationException
     */
    public function resolve(OidcIdentity $identity): User
    {
        return DB::transaction(function () use ($identity): User {
            $externalIdentity = UserIdentity::query()
                ->where('provider', 'oidc')
                ->where('issuer', $identity->issuer)
                ->where('subject', $identity->subject)
                ->first();

            if ($externalIdentity) {
                $user = $externalIdentity->user;

                if (! $user) {
                    throw new AuthenticationException('SSO identity is not linked to an existing local user.');
                }

                $this->updateExternalIdentity($externalIdentity, $identity);
                $this->afterSuccessfulLogin($user, $identity);

                return $user;
            }

            $mode = (string) config('sso.oidc.provisioning_mode', 'disabled');

            if ($mode === 'disabled') {
                throw new AuthenticationException('SSO account is not linked to a local user.');
            }

            $this->validateEmailRules($identity);

            return match ($mode) {
                'invited_only' => $this->linkExistingUserByVerifiedEmail($identity),
                'auto' => $this->provisionUser($identity),
                default => throw new AuthenticationException('Invalid SSO provisioning mode.'),
            };
        });
    }

    /**
     * @throws AuthenticationException
     */
    private function validateEmailRules(OidcIdentity $identity): void
    {
        if ($identity->email === null) {
            throw new AuthenticationException('SSO email address is required for this provisioning mode.');
        }

        if ((bool) config('sso.oidc.require_verified_email', true) && ! $identity->emailVerified) {
            throw new AuthenticationException('SSO email address is not verified.');
        }

        $allowedDomains = array_map(
            static fn (string $domain): string => Str::lower($domain),
            config('sso.oidc.allowed_domains', [])
        );

        if ($allowedDomains === []) {
            return;
        }

        $domain = Str::lower(Str::afterLast($identity->email, '@'));

        if (! in_array($domain, $allowedDomains, true)) {
            throw new AuthenticationException('SSO email domain is not allowed.');
        }
    }

    /**
     * @throws AuthenticationException
     */
    private function linkExistingUserByVerifiedEmail(OidcIdentity $identity): User
    {
        if ($identity->email === null || ! $identity->emailVerified) {
            throw new AuthenticationException('Verified SSO email address is required for invited-only provisioning.');
        }

        $user = User::query()
            ->where('email', Str::lower($identity->email))
            ->first();

        if (! $user) {
            throw new AuthenticationException('No local user exists for this SSO identity.');
        }

        $this->createExternalIdentity($user, $identity);
        $this->afterSuccessfulLogin($user, $identity);

        return $user;
    }

    /**
     * @throws AuthenticationException
     */
    private function provisionUser(OidcIdentity $identity): User
    {
        if ($identity->email === null) {
            throw new AuthenticationException('SSO email address is required for auto provisioning.');
        }

        if (User::query()->where('email', Str::lower($identity->email))->exists()) {
            throw new AuthenticationException('A local user with this email already exists.');
        }

        $site = Site::default();
        $defaultRole = (string) config('sso.oidc.auto_provision.default_role', 'user');

        if (! Role::query()->where('name', $defaultRole)->where('guard_name', 'web')->exists()) {
            throw new AuthenticationException('The configured SSO default role does not exist.');
        }

        $user = User::query()->create([
            'site_id' => $site->id,
            'department_id' => $this->configuredDepartmentId(),
            'name' => $this->extractLastName($identity),
            'first_name' => $this->extractFirstName($identity),
            'email' => Str::lower($identity->email),
            'email_verified_at' => $identity->emailVerified ? now() : null,
            'password' => Hash::make(Str::random(64)),
            'is_active' => true,
            'local_login_allowed' => false,
        ]);

        $user->assignRole($defaultRole);

        $this->createExternalIdentity($user, $identity);
        $this->afterSuccessfulLogin($user, $identity);

        return $user;
    }

    /**
     * @throws AuthenticationException
     */
    private function afterSuccessfulLogin(User $user, OidcIdentity $identity): void
    {
        if (! $user->is_active) {
            throw new AuthenticationException('User account is disabled.');
        }

        if ((bool) config('sso.oidc.sync_user_profile', true)) {
            $this->profileSync->sync($user, $identity);
        }

        if ((bool) config('sso.oidc.sync_roles', false) && (bool) config('sso.oidc.sync_roles_on_login', false)) {
            $this->roleMapper->sync($user, $identity);
        }
    }

    private function createExternalIdentity(User $user, OidcIdentity $identity): UserIdentity
    {
        return UserIdentity::query()->create($this->identityAttributes($user, $identity));
    }

    private function updateExternalIdentity(UserIdentity $externalIdentity, OidcIdentity $identity): void
    {
        $externalIdentity->forceFill($this->identityAttributes($externalIdentity->user, $identity))->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function identityAttributes(User $user, OidcIdentity $identity): array
    {
        return [
            'user_id' => $user->id,
            'provider' => 'oidc',
            'provider_user_id' => $identity->subject,
            'tenant_id' => $this->stringClaim($identity, 'tid'),
            'issuer' => $identity->issuer,
            'subject' => $identity->subject,
            'email_at_provider' => $identity->email !== null ? Str::lower($identity->email) : null,
            'email_verified' => $identity->emailVerified,
            'display_name' => $identity->displayName,
            'claims' => (bool) config('sso.oidc.store_claims', false) ? $identity->claims : null,
            'last_login_at' => now(),
        ];
    }

    private function extractFirstName(OidcIdentity $identity): string
    {
        $givenName = $this->stringClaim($identity, 'given_name');

        if ($givenName !== null && $givenName !== '') {
            return $givenName;
        }

        $displayName = trim((string) $identity->displayName);

        if ($displayName !== '') {
            return Str::before($displayName, ' ') ?: $displayName;
        }

        return 'SSO';
    }

    private function extractLastName(OidcIdentity $identity): string
    {
        $familyName = $this->stringClaim($identity, 'family_name');

        if ($familyName !== null && $familyName !== '') {
            return $familyName;
        }

        $displayName = trim((string) $identity->displayName);

        if (Str::contains($displayName, ' ')) {
            return Str::after($displayName, ' ');
        }

        return 'User';
    }

    private function configuredDepartmentId(): ?int
    {
        $departmentId = config('sso.oidc.auto_provision.default_department_id');

        return blank($departmentId) ? null : (int) $departmentId;
    }

    private function stringClaim(OidcIdentity $identity, string $claim): ?string
    {
        $value = $identity->claims[$claim] ?? null;

        return is_string($value) ? $value : null;
    }
}
