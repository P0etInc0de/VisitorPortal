<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Department');
    }

    public function view(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('View:Department');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Department');
    }

    public function update(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Update:Department');
    }

    public function delete(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('Delete:Department');
    }

}