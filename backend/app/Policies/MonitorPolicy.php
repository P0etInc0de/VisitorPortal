<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Monitor;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class MonitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Monitor');
    }

    public function view(User $user): bool
    {
        return $user->can('View:Monitor') || $user->can('ViewAny:Monitor');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Monitor');
    }

    public function update(User $user): bool
    {
        return $user->can('Edit:Monitor');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete:Monitor');
    }

    public function edit(User $user): bool
    {
        return $user->can('Edit:Monitor');
    }
}
