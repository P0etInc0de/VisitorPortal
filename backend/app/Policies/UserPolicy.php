<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:User');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User');
    }

    public function viewOwn(AuthUser $authUser): bool
    {
        return $authUser->can('ViewOwn:User');
    }

    public function editOwn(AuthUser $authUser): bool
    {
        return $authUser->can('EditOwn:User');
    }

    public function viewDepartment(AuthUser $authUser): bool
    {
        return $authUser->can('ViewDepartment:User');
    }

    public function editDepartment(AuthUser $authUser): bool
    {
        return $authUser->can('EditDepartment:User');
    }

    public function deleteDepartment(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteDepartment:User');
    }

    public function deactivateAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeactivateAny:User');
    }

    public function deactivateDepartment(AuthUser $authUser): bool
    {
        return $authUser->can('DeactivateDepartment:User');
    }

    public function editAny(AuthUser $authUser): bool
    {
        return $authUser->can('EditAny:User');
    }
}
