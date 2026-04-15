<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Visitor;
use Illuminate\Auth\Access\HandlesAuthorization;
//use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\User;

class VisitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Visitor');
    }

    public function view(User $user, Visitor $visitor): bool
    {
        return $user->can('View:Visitor');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Visitor');
    }

    public function update(User $user, Visitor $visitor): bool
    {
        return $user->can('Update:Visitor');
    }

    public function delete(User $user, Visitor $visitor): bool
    {
        return $user->can('Delete:Visitor');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Visitor');
    }

    public function editAny(User $user): bool
    {
        return $user->can('EditAny:Visitor');
    }
}
