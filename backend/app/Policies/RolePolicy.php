<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Role');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('View:Role');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Role');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('Update:Role');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('Delete:Role');
    }
}
