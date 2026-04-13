<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Visitor;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('viewAny:visitor');
    }

    public function view(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('view:visitor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create:visitor');
    }

    public function update(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('update:visitor');
    }

    public function delete(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('delete:visitor');
    }

}