<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Tasks;

use App\Services\RecurringVisitService;

class RecurringVisitSeriesExpansion
{
    public function __invoke(): void
    {
        app(RecurringVisitService::class)->fillAllForeverHorizons();
    }
}
