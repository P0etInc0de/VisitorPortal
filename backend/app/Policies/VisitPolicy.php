<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class VisitPolicy
{
    use HandlesAuthorization;

    /**
     * @return EloquentCollection<int, User>
     */
    public function assignableUsers(User $user, ?Visit $visit = null): EloquentCollection
    {
        $query = User::query()->select('id', 'first_name', 'name', 'title', 'department_id');

        if ($this->editAny($user)) {
            // keep full query
        } elseif ($user->can('EditDepartment:Visit') && filled($user->department_id)) {
            $query->where('department_id', $user->department_id);
        } else {
            $query->whereKey($user->id);
        }

        $users = $query
            ->orderBy('first_name')
            ->orderBy('name')
            ->get();

        $requiredIds = collect([
            $user->id,
            $visit?->user_id,
            $visit?->responsible_user_id,
        ])->filter()->unique();

        $missingIds = $requiredIds->diff($users->modelKeys());

        if ($missingIds->isNotEmpty()) {
            $users = $users->concat(
                User::query()
                    ->select('id', 'first_name', 'name', 'title', 'department_id')
                    ->whereIn('id', $missingIds)
                    ->get()
            );
        }

        return $users
            ->sortBy(fn (User $candidate) => ($candidate->id === $user->id ? '0-' : '1-').mb_strtolower(trim($candidate->first_name.' '.$candidate->name)))
            ->values();
    }

    /**
     * @return array<int, int>
     */
    public function assignableUserIds(User $user, ?Visit $visit = null): array
    {
        return $this->assignableUsers($user, $visit)
            ->modelKeys();
    }

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Visit');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $this->viewAny($user) || $this->viewOwn($user, $visit) || $this->viewDepartment($user, $visit);
    }

    public function viewGeneral(User $user): bool
    {
        return $user->can('ViewAny:Visit') || $user->can('ViewDepartment:Visit') || $user->can('ViewOwn:Visit');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Visit');
    }

    public function update(User $user, Visit $visit): bool
    {
        return $this->editAny($user, $visit) || $this->editDepartment($user, $visit) || $this->editOwn($user, $visit);
    }

    public function delete(User $user, Visit $visit): bool
    {
        return $this->deleteAny($user, $visit) || $this->deleteDepartment($user, $visit) || $this->deleteOwn($user, $visit);
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

    public function checkIn(User $user): bool
    {
        return $user->can('CheckIn:Visit');
    }

    public function checkOut(User $user): bool
    {
        return $user->can('CheckOut:Visit');
    }

    public function print(User $user, Visit $visit): bool
    {
        return $user->can('Print:Visit');
    }
}
