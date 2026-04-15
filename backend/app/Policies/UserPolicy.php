<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
//use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\User;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:User');
    }

    public function view(User $user): bool
    {
        return $user->can('View:User');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:User');
    }

    public function update(User $user): bool
    {
        return $user->can('Update:User');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete:User');
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
        return $user->can('ViewDepartment:User') && $user->department_id === $requested_user->department_id;
    }

    public function editDepartment(User $user, User $requested_user): bool
    {
        return $user->can('EditDepartment:User') && $user->department_id === $requested_user->department_id;
    }

    public function deleteDepartment(User $user, User $requested_user): bool
    {
        return $user->can('DeleteDepartment:User') && $user->department_id === $requested_user->department_id;
    }

    public function deactivateAny(User $user): bool
    {
        return $user->can('DeactivateAny:User');
    }

    public function deactivateDepartment(User $user, User $requested_user): bool
    {
        return $user->can('DeactivateDepartment:User') && $user->department_id === $requested_user->department_id;
    }

    public function editAny(User $user): bool
    {
        return $user->can('EditAny:User');
    }
}
