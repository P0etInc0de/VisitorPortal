<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Models\User;

final class AppMfaPolicy
{
    public function isRequiredForAnyContext(User $user): bool
    {
        if (! $this->isEnabledForRequiredRole($user)) {
            return false;
        }

        return config('security.mfa.app_required_for_auth_methods', ['local']) !== []
            || config('security.mfa.app_required_for_admin_panel_auth_methods', ['local', 'sso']) !== [];
    }

    public function isRequiredForAuthMethod(User $user, string $authMethod): bool
    {
        if (! $this->isEnabledForRequiredRole($user)) {
            return false;
        }

        return in_array($authMethod, config('security.mfa.app_required_for_auth_methods', ['local']), true);
    }

    public function isRequiredForAdminPanel(User $user, string $authMethod): bool
    {
        if (! $this->isEnabledForRequiredRole($user)) {
            return false;
        }

        return in_array($authMethod, config('security.mfa.app_required_for_admin_panel_auth_methods', ['local', 'sso']), true);
    }

    private function isEnabledForRequiredRole(User $user): bool
    {
        return (bool) config('security.mfa.enabled')
            && $user->hasAnyRole(config('security.mfa.app_required_roles', ['admin', 'super_admin']));
    }
}
