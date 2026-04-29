<?php

namespace Tests\Support;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Collection;

class VisitHelper
{
    // returns a visit based on given parameters
    public function makeVisit(
        int $user_id,
        string $title,
        VisitStatusEnum $status,
        int $responsible_user_id,
        ?Collection $visitors = null
    ): Visit {

        // create a visit
        $visit = Visit::factory()->create([
            'title' => $title,
            'status' => $status,
            'responsible_user_id' => $responsible_user_id,
            'user_id' => $user_id,
        ]);

        // check if visitors parameter is passed and add them, if so
        if ($visitors && $visitors->isNotEmpty()) {
            $visit->visitors()->sync($visitors->pluck('id')->toArray());
        }

        return $visit;
    }
}
