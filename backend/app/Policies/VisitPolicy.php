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
        return $authUser->can('ViewAny:Visit');
    }

    public function view(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('View:Visit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Visit');
    }

    public function update(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('Update:Visit');
    }

    public function delete(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('Delete:Visit');
    }

    public function deleteAny(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('DeleteAny:Visit');
    }

    public function editAny(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('EditAny:Visit');
    }

    public function viewDepartment(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('ViewDepartment:Visit');
    }

    public function viewOwn(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('ViewOwn:Visit');
    }

    public function editDepartment(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('EditDepartment:Visit');
    }

    public function editOwn(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('EditOwn:Visit');
    }

    public function deleteDepartment(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('DeleteDepartment:Visit');
    }

    public function deleteOwn(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('DeleteOwn:Visit');
    }

    public function checkIn(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('CheckIn:Visit');
    }

    public function checkOut(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('CheckOut:Visit');
    }

    public function print(AuthUser $authUser, Visit $visit): bool
    {
        return $authUser->can('Print:Visit');
    }

}