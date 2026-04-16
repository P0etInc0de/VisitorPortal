<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Monitor;
use Illuminate\Auth\Access\HandlesAuthorization;
//use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\User;

class MonitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Monitor');
    }

    public function view(User $user, Monitor $monitor): bool
    {
        return $user->can('View:Monitor');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Monitor');
    }

    public function update(User $user, Monitor $monitor): bool
    {
        return $user->can('Update:Monitor');
    }

    public function delete(User $user, Monitor $monitor): bool
    {
        return $user->can('Delete:Monitor');
    }

    public function edit(User $user): bool
    {
        return $user->can('Edit:Monitor');
    }
}
