<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Self-registration is intentionally disabled for this product.
        abort(404);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store()
    {
        // Self-registration is intentionally disabled for this product.
        abort(404);
    }
}
