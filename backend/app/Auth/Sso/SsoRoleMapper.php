<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Auth\Sso;

use App\Auth\Sso\DTO\OidcIdentity;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

final class SsoRoleMapper
{
    public function sync(User $user, OidcIdentity $identity): void
    {
        $mapping = config('sso.oidc.role_mapping', []);

        $roles = collect($identity->groups)
            ->filter(static fn (mixed $group): bool => is_string($group) && array_key_exists($group, $mapping))
            ->map(static fn (string $group): mixed => $mapping[$group])
            ->filter(static fn (mixed $role): bool => is_string($role) && $role !== '')
            ->unique()
            ->values();

        $removeUnmapped = (bool) config('sso.oidc.sync_roles_remove_unmapped', false);

        if ($roles->isEmpty()) {
            if ($removeUnmapped) {
                $user->syncRoles([]);
            }

            return;
        }

        $this->ensureMappedRolesExist($roles->all());

        if ($removeUnmapped) {
            $user->syncRoles($roles->all());

            return;
        }

        $user->assignRole($roles->all());
    }

    /**
     * @param  list<string>  $roles
     */
    private function ensureMappedRolesExist(array $roles): void
    {
        $existingRoles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();

        $missingRoles = array_values(array_diff($roles, $existingRoles));

        if ($missingRoles === []) {
            return;
        }

        Log::channel('web')->critical('sso_role_mapping_invalid', [
            'missing_roles' => $missingRoles,
        ]);

        throw new SsoAuthenticationException('Invalid SSO role mapping.');
    }
}
