<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Monitor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MonitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Monitor');
    }

    public function view(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('View:Monitor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Monitor');
    }

    public function update(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('Update:Monitor');
    }

    public function delete(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('Delete:Monitor');
    }

    public function edit(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('Edit:Monitor');
    }
}
