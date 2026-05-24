<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\Monitor;
use App\Models\User;
// use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class MonitorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Monitor') || $user->can('ViewSite:Monitor');
    }

    public function view(User $user, ?Monitor $monitor = null): bool
    {
        if ($user->can('ViewAny:Monitor') || $user->can('ManageAny:Monitor')) {
            return true;
        }

        if (! $monitor) {
            return $user->can('View:Monitor') || $user->can('ViewSite:Monitor');
        }

        return ($user->can('View:Monitor') || $user->can('ViewSite:Monitor') || $user->can('ManageSite:Monitor'))
            && $user->canAccessSite($monitor->site_id);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Monitor') || $user->can('ManageAny:Monitor') || $user->can('ManageSite:Monitor');
    }

    public function update(User $user, ?Monitor $monitor = null): bool
    {
        if ($user->can('ManageAny:Monitor')) {
            return true;
        }

        if (! $monitor) {
            return $user->can('Edit:Monitor') || $user->can('ManageSite:Monitor');
        }

        return ($user->can('Edit:Monitor') || $user->can('ManageSite:Monitor'))
            && $user->canAccessSite($monitor->site_id);
    }

    public function delete(User $user, ?Monitor $monitor = null): bool
    {
        if ($user->can('ManageAny:Monitor')) {
            return true;
        }

        if (! $monitor) {
            return $user->can('Delete:Monitor') || $user->can('ManageSite:Monitor');
        }

        return ($user->can('Delete:Monitor') || $user->can('ManageSite:Monitor'))
            && $user->canAccessSite($monitor->site_id);
    }

    public function edit(User $user, ?Monitor $monitor = null): bool
    {
        return $this->update($user, $monitor);
    }
}
