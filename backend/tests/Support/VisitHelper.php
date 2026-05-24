<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Support;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Collection;

class VisitHelper
{
    // returns a visit based on given parameters
    public function makeVisit(
        int $hostUserId,
        string $title,
        VisitStatusEnum $status,
        ?int $substituteUserId,
        ?Collection $visitors = null
    ): Visit {

        // create a visit
        $visit = Visit::factory()->create([
            'title' => $title,
            'status' => $status,
            'host_user_id' => $hostUserId,
            'substitute_user_id' => $substituteUserId,
            'created_by_user_id' => $hostUserId,
        ]);

        // check if visitors parameter is passed and add them, if so
        if ($visitors && $visitors->isNotEmpty()) {
            $visit->visitors()->sync($visitors->pluck('id')->toArray());
        }

        return $visit;
    }
}
