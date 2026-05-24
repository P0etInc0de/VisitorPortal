<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Site') || $user->can('ManageAny:Site');
    }

    public function view(User $user, Site $site): bool
    {
        return $user->can('ViewAny:Site') || $user->can('ManageAny:Site');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Site') || $user->can('ManageAny:Site');
    }

    public function update(User $user, Site $site): bool
    {
        return $user->can('Update:Site') || $user->can('ManageAny:Site');
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->can('Delete:Site') || $user->can('ManageAny:Site');
    }
}
