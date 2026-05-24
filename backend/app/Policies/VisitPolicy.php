<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Visit;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class VisitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Visit') || $user->can('ViewSite:Visit') || $user->can('EditSite:Visit') || $user->can('DeleteSite:Visit');
    }

    public function view(User $user, Visit $visit): bool
    {
        return $this->viewAnyGlobal($user) || $this->viewSite($user, $visit) || $this->viewOwn($user, $visit) || $this->viewDepartment($user, $visit);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Visit');
    }

    public function update(User $user, Visit $visit): bool
    {
        if ($this->editAny($user)) {
            return true;
        }

        if ($this->isHistorical($visit)) {
            return false;
        }

        return $this->editSite($user, $visit) || $this->editDepartment($user, $visit) || $this->editOwn($user, $visit);
    }

    public function delete(User $user, Visit $visit): bool
    {
        if ($this->deleteAny($user)) {
            return true;
        }

        if ($this->isHistorical($visit)) {
            return false;
        }

        return $this->deleteSite($user, $visit) || $this->deleteDepartment($user, $visit) || $this->deleteOwn($user, $visit);
    }

    public function cancel(User $user, Visit $visit): bool
    {
        if ($this->deleteAny($user) || $user->can('CancelAny:Visit')) {
            return true;
        }

        if ($this->isHistorical($visit)) {
            return false;
        }

        return $this->cancelSite($user, $visit)
            || $this->cancelDepartment($user, $visit)
            || $this->cancelOwn($user, $visit)
            || $this->deleteSite($user, $visit)
            || $this->deleteDepartment($user, $visit)
            || $this->deleteOwn($user, $visit);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Visit');
    }

    public function editAny(User $user): bool
    {
        return $user->can('EditAny:Visit');
    }

    public function viewSite(User $user, Visit $visit): bool
    {
        return $user->can('ViewSite:Visit')
            && $user->canAccessSite($visit->site_id);
    }

    public function editSite(User $user, Visit $visit): bool
    {
        return $user->can('EditSite:Visit')
            && $user->canAccessSite($visit->site_id);
    }

    public function deleteSite(User $user, Visit $visit): bool
    {
        return $user->can('DeleteSite:Visit')
            && $user->canAccessSite($visit->site_id);
    }

    public function cancelSite(User $user, Visit $visit): bool
    {
        return $user->can('CancelSite:Visit')
            && $user->canAccessSite($visit->site_id);
    }

    public function viewDepartment(User $user, Visit $visit): bool
    {
        return $user->can('ViewDepartment:Visit')
            && $user->department_id !== null
            && ($user->department_id === $visit->host?->department_id || $user->department_id === $visit->substituteUser?->department_id);
    }

    public function viewOwn(User $user, Visit $visit): bool
    {
        return $user->can('ViewOwn:Visit') && ($user->id === $visit->host_user_id || $user->id === $visit->substitute_user_id);
    }

    public function editDepartment(User $user, Visit $visit): bool
    {
        return $user->can('EditDepartment:Visit')
            && $user->department_id !== null
            && ($user->department_id === $visit->host?->department_id || $user->department_id === $visit->substituteUser?->department_id);
    }

    public function editOwn(User $user, Visit $visit): bool
    {
        return $user->can('EditOwn:Visit') && ($user->id === $visit->host_user_id || $user->id === $visit->substitute_user_id);
    }

    public function deleteDepartment(User $user, Visit $visit): bool
    {
        return $user->can('DeleteDepartment:Visit')
            && $user->department_id !== null
            && ($user->department_id === $visit->host?->department_id || $user->department_id === $visit->substituteUser?->department_id);
    }

    public function cancelDepartment(User $user, Visit $visit): bool
    {
        return $user->can('CancelDepartment:Visit')
            && $user->department_id !== null
            && ($user->department_id === $visit->host?->department_id || $user->department_id === $visit->substituteUser?->department_id);
    }

    public function deleteOwn(User $user, Visit $visit): bool
    {
        return $user->can('DeleteOwn:Visit') && ($user->id === $visit->host_user_id || $user->id === $visit->substitute_user_id);
    }

    public function cancelOwn(User $user, Visit $visit): bool
    {
        return $user->can('CancelOwn:Visit') && ($user->id === $visit->host_user_id || $user->id === $visit->substitute_user_id);
    }

    private function isHistorical(Visit $visit): bool
    {
        if (in_array($visit->status, ['completed', 'canceled'], true)) {
            return true;
        }

        $hasCheckedInVisitors = $visit->visitors()
            ->wherePivotNotNull('checked_in_at')
            ->exists();

        if (! $hasCheckedInVisitors) {
            return false;
        }

        return ! $visit->visitors()
            ->wherePivotNull('checked_out_at')
            ->exists();
    }

    private function viewAnyGlobal(User $user): bool
    {
        return $user->can('ViewAny:Visit');
    }
}
