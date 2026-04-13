<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('viewAny:visit');
    }

    public function view(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('view:visit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:visit');
    }

    public function update(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('update:visit');
    }

    public function delete(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('delete:visit');
    }

}