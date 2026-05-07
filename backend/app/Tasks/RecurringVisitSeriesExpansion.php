<?php

namespace App\Tasks;

use App\Services\RecurringVisitService;

class RecurringVisitSeriesExpansion
{
    public function __invoke(): void
    {
        app(RecurringVisitService::class)->fillAllForeverHorizons();
    }
}
