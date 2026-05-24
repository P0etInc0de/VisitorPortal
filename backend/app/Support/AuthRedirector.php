<?php
/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Models\Monitor;
use App\Models\User;

class AuthRedirector
{
    public function pathFor(User $user): string
    {
        if ($user->hasAnyRole(['welcome monitor', 'welcome_monitor'])) {
            $siteMonitor = Monitor::query()
                ->whereIn('site_id', $user->assignedSiteIds()->all())
                ->orderByRaw('CASE WHEN site_id = ? THEN 0 ELSE 1 END', [(int) $user->site_id])
                ->first();

            return $siteMonitor
                ? route('monitors.show', $siteMonitor, absolute: false)
                : route('monitors.missing', absolute: false);
        }

        return route('overview', absolute: false);
    }
}
