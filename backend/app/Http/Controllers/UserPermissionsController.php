<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers;

class UserPermissionsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $this->authorize('view', Role::class);

        $data = [
            'name' => $user->name,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()
                ->pluck('name')
                ->filter(fn ($p) => str_contains($p, ':'))
                ->groupBy(fn ($p) => explode(':', $p)[1] ?? 'general')
                ->map(fn ($perms) => $perms->map(fn ($p) => explode(':', $p)[0])),
        ];

        return view('profile.user-permissions', compact('data'));
    }
}
