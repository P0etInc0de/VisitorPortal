<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Contracts\View\View;

class VisitController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Visit::class);

        return view('reception.all-visits');
    }
}
