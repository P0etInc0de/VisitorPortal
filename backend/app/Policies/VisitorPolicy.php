<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Visitor;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Visitor') || $user->can('ViewSite:Visitor') || $user->can('ViewDepartment:Visitor') || $user->can('ViewKnown:Visitor');
    }

    public function view(User $user, Visitor $visitor): bool
    {
        return Visitor::query()
            ->visibleTo($user)
            ->whereKey($visitor->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Visitor');
    }

    public function update(User $user, Visitor $visitor): bool
    {
        return ($user->can('Update:Visitor') || $user->can('UpdateKnown:Visitor') || $user->can('UpdateAny:Visitor') || $user->can('EditAny:Visitor'))
            && Visitor::query()
                ->visibleTo($user)
                ->whereKey($visitor->id)
                ->exists();
    }

    public function delete(User $user, Visitor $visitor): bool
    {
        return ($user->can('Delete:Visitor') || $user->can('DeleteAny:Visitor'))
            && Visitor::query()
                ->visibleTo($user)
                ->whereKey($visitor->id)
                ->exists();
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Visitor');
    }

    public function editAny(User $user): bool
    {
        return $user->can('EditAny:Visitor');
    }

    public function checkIn(User $user): bool
    {
        return $user->can('CheckIn:Visitor');
    }

    public function checkOut(User $user): bool
    {
        return $user->can('CheckOut:Visitor');
    }

    public function print(User $user): bool
    {
        return $user->can('Print:Visitor');
    }
}
