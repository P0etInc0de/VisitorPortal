<?php

namespace App\Tasks;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Builder;

class CompleteFinishedVisits
{
    public function __invoke(): int
    {
        return Visit::query()
            ->where('status', VisitStatusEnum::Planned->value)
            ->whereNull('canceled_at')
            ->where('scheduled_until', '<=', now())
            ->whereHas('visitors')
            ->whereDoesntHave('visitors', function (Builder $query): void {
                $query->whereNotNull('visit_visitor.checked_in_at')
                    ->whereNull('visit_visitor.checked_out_at');
            })
            ->update([
                'status' => VisitStatusEnum::Completed->value,
            ]);
    }
}
