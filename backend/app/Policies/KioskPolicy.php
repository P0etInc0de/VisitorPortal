<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kiosk;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KioskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Kiosk')
            || $user->can('ViewSite:Kiosk')
            || $user->can('ManageAny:Kiosk')
            || $user->can('ManageSite:Kiosk');
    }

    public function view(User $user, ?Kiosk $kiosk = null): bool
    {
        if ($user->can('ViewAny:Kiosk') || $user->can('ManageAny:Kiosk')) {
            return true;
        }

        if (! $kiosk) {
            return $user->can('View:Kiosk') || $user->can('ViewSite:Kiosk');
        }

        return ($user->can('View:Kiosk') || $user->can('ViewSite:Kiosk') || $user->can('ManageSite:Kiosk'))
            && $user->canAccessSite($kiosk->site_id);
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Kiosk') || $user->can('ManageAny:Kiosk') || $user->can('ManageSite:Kiosk');
    }

    public function update(User $user, ?Kiosk $kiosk = null): bool
    {
        if ($user->can('ManageAny:Kiosk')) {
            return true;
        }

        if (! $kiosk) {
            return $user->can('Update:Kiosk') || $user->can('ManageSite:Kiosk');
        }

        return ($user->can('Update:Kiosk') || $user->can('ManageSite:Kiosk'))
            && $user->canAccessSite($kiosk->site_id);
    }

    public function delete(User $user, ?Kiosk $kiosk = null): bool
    {
        if ($user->can('ManageAny:Kiosk')) {
            return true;
        }

        if (! $kiosk) {
            return $user->can('Delete:Kiosk') || $user->can('ManageSite:Kiosk');
        }

        return ($user->can('Delete:Kiosk') || $user->can('ManageSite:Kiosk'))
            && $user->canAccessSite($kiosk->site_id);
    }

    public function edit(User $user, ?Kiosk $kiosk = null): bool
    {
        return $this->update($user, $kiosk);
    }
}
