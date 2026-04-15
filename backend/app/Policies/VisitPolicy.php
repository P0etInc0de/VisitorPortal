<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Visit;
use Illuminate\Auth\Access\HandlesAuthorization;
//use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\User;

class VisitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Visit');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $user->can('View:Visit');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Visit');
    }

    public function update(User $user, Visit $visit): bool
    {
        return $user->can('Update:Visit');
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $user->can('Delete:Visit');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Visit');
    }

    public function editAny(User $user): bool
    {
        return $user->can('EditAny:Visit');
    }

    public function viewDepartment(User $user, Visit $visit): bool
    {
        return $user->can('ViewDepartment:Visit') && ($user->department_id === $visit->host->department_id || $user->department_id === $visit->responsibleUser->department_id);
    }

    public function viewOwn(User $user, Visit $visit): bool
    {
        return $user->can('ViewOwn:Visit') && ($user->id === $visit->user_id || $user->id === $visit->responsible_user_id);
    }

    public function editDepartment(User $user, Visit $visit): bool
    {
        return $user->can('EditDepartment:Visit') && ($user->department_id === $visit->host->department_id || $user->department_id === $visit->responsibleUser->department_id);
    }

    public function editOwn(User $user, Visit $visit): bool
    {
        return $user->can('EditOwn:Visit') && ($user->id === $visit->user_id || $user->id === $visit->responsible_user_id);
    }

    public function deleteDepartment(User $user, Visit $visit): bool
    {
        return $user->can('DeleteDepartment:Visit') && ($user->department_id === $visit->host->department_id || $user->department_id === $visit->responsibleUser->department_id);
    }

    public function deleteOwn(User $user, Visit $visit): bool
    {
        return $user->can('DeleteOwn:Visit') && ($user->id === $visit->user_id || $user->id === $visit->responsible_user_id);
    }

    public function checkIn(User $user, Visit $visit): bool
    {
        return $user->can('CheckIn:Visit');
    }

    public function checkOut(User $user, Visit $visit): bool
    {
        return $user->can('CheckOut:Visit');
    }

    public function print(User $user, Visit $visit): bool
    {
        return $user->can('Print:Visit');
    }
}
