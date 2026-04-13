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
        return $authUser->can('viewAny:department');
    }

    public function view(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('view:department');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:department');
    }

    public function update(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('update:department');
    }

    public function delete(AuthUser $authUser, Department $department): bool
    {
        return $authUser->can('delete:department');
    }

}