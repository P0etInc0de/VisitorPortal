<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\MonitorSlide;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MonitorSlidePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MonitorSlide');
    }

    public function view(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('View:MonitorSlide');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MonitorSlide');
    }

    public function update(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('Update:MonitorSlide');
    }

    public function delete(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('Delete:MonitorSlide');
    }

    public function forceDelete(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('ForceDelete:MonitorSlide');
    }

    public function forceDeleteAny(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('ForceDeleteAny:MonitorSlide');
    }

    public function reorder(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('Reorder:MonitorSlide');
    }

    public function replicate(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('Replicate:MonitorSlide');
    }

    public function restore(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('Restore:MonitorSlide');
    }

    public function restoreAny(AuthUser $authUser, MonitorSlide $monitorSlide): bool
    {
        return $authUser->can('RestoreAny:MonitorSlide');
    }
}
