<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Monitor;
use Illuminate\Auth\Access\HandlesAuthorization;

class MonitorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('viewAny:monitor');
    }

    public function view(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('view:monitor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:monitor');
    }

    public function update(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('update:monitor');
    }

    public function delete(AuthUser $authUser, Monitor $monitor): bool
    {
        return $authUser->can('delete:monitor');
    }

}