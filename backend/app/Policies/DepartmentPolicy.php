<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;
//use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\User;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Department');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('View:Department');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Department');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('Update:Department');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('Delete:Department');
    }
}
