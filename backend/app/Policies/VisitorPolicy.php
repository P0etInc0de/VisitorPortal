<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Visitor;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VisitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Visitor');
    }

    public function view(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('View:Visitor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Visitor');
    }

    public function update(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('Update:Visitor');
    }

    public function delete(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('Delete:Visitor');
    }

    public function deleteAny(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('DeleteAny:Visitor');
    }

    public function editAny(AuthUser $authUser, Visitor $visitor): bool
    {
        return $authUser->can('EditAny:Visitor');
    }
}
