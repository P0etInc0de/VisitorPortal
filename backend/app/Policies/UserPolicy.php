<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Policies;

use App\Models\User;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:User');
    }

    public function view(User $user, User $requested_user): bool
    {
        return $this->viewAny($user) || $this->viewDepartment($user, $requested_user) || $this->viewOwn($user, $requested_user);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:User');
    }

    public function update(User $user, User $requested_user): bool
    {
        return $this->editAny($user) || $this->editDepartment($user, $requested_user) || $this->editOwn($user, $requested_user);
    }

    public function delete(User $user, User $requested_user): bool
    {
        return $this->deleteAny($user) || $this->deleteDepartment($user, $requested_user);
    }

    public function deactivate(User $user, User $requested_user): bool
    {
        return $this->deactivateAny($user) || $this->deactivateDepartment($user, $requested_user);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:User');
    }

    public function viewOwn(User $user, User $requested_user): bool
    {
        return $user->can('ViewOwn:User') && $user->id === $requested_user->id;
    }

    public function editOwn(User $user, User $requested_user): bool
    {
        return $user->can('EditOwn:User') && $user->id === $requested_user->id;
    }

    public function viewDepartment(User $user, User $requested_user): bool
    {
        return $user->can('ViewDepartment:User')
            && $user->department_id !== null
            && $user->department_id === $requested_user->department_id;
    }

    public function editDepartment(User $user, User $requested_user): bool
    {
        return $user->can('EditDepartment:User')
            && $user->department_id !== null
            && $user->department_id === $requested_user->department_id;
    }

    public function deleteDepartment(User $user, User $requested_user): bool
    {
        return $user->can('DeleteDepartment:User')
            && $user->department_id !== null
            && $user->department_id === $requested_user->department_id;
    }

    public function deactivateAny(User $user): bool
    {
        return $user->can('DeactivateAny:User');
    }

    public function deactivateDepartment(User $user, User $requested_user): bool
    {
        return $user->can('DeactivateDepartment:User')
            && $user->department_id !== null
            && $user->department_id === $requested_user->department_id;
    }

    public function editAny(User $user): bool
    {
        return $user->can('EditAny:User');
    }
}
