<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ReceptionPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_reception_pages_can_be_rendered(): void
    {
        $user = (new PermissionHelper)->getReceptionistUser();

        $this->actingAs($user)->get(route('reception.dashboard'))->assertOk();
        $this->actingAs($user)->get(route('reception.all-visits'))->assertOk();
    }
}
