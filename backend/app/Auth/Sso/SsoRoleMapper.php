<?php

namespace App\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\User;

final class SsoRoleMapper
{
    public function sync(User $user, OidcIdentity $identity): void
    {
        $mapping = config('sso.oidc.role_mapping', []);

        $roles = collect($identity->groups)
            ->filter(static fn (mixed $group): bool => is_string($group) && array_key_exists($group, $mapping))
            ->map(static fn (string $group): string => $mapping[$group])
            ->unique()
            ->values();

        $removeUnmapped = (bool) config('sso.oidc.sync_roles_remove_unmapped', false);

        if ($roles->isEmpty()) {
            if ($removeUnmapped) {
                $user->syncRoles([]);
            }

            return;
        }

        if ($removeUnmapped) {
            $user->syncRoles($roles->all());

            return;
        }

        $user->assignRole($roles->all());
    }
}
