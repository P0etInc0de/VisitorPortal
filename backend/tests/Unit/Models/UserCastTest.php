<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit\Models;

use App\Enums\GenderEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCastTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_casts_include_local_login_flag_and_gender_enum(): void
    {
        $user = User::factory()->create([
            'gender' => GenderEnum::Female->value,
            'local_login_allowed' => 0,
        ])->fresh();

        $this->assertSame(GenderEnum::Female, $user->gender);
        $this->assertIsBool($user->local_login_allowed);
        $this->assertFalse($user->local_login_allowed);
    }
}
