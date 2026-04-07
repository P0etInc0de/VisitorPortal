<?php

namespace Database\Seeders;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VisitSeeder extends Seeder
{
    /**
     * Seeder for visits table
     */
    public function run(): void
    {
        $this->createOrUpdateVisit(1, 2, Carbon::create(2027, 4, 4, 14, 0,0), Carbon::create(2027, 4, 4, 15, 0,0), VisitStatusEnum::Planned, 'Forza Meeting2');
        $this->createOrUpdateVisit(1, 2, Carbon::now()->setHour(13)->setMinute(0), Carbon::now()->setHour(14)->setMinute(0), VisitStatusEnum::Planned, 'Forza Meeting1');
        $this->createOrUpdateVisit(2, 1, Carbon::yesterday()->setHour(10)->setMinute(0), Carbon::yesterday()->setHour(11)->setMinute(30), VisitStatusEnum::Cancelled, 'Piratenpizza im Mountainbikepark');
    }

    private function createOrUpdateVisit(int $userId, int $respUserId, Carbon $from, Carbon $until, VisitStatusEnum $status, ?string $notes): void
    {

        $visit = Visit::where('user_id', $userId)
            ->where('scheduled_from', $from)
            ->first();

        if (! $visit) {
            $visit = Visit::forceCreate([
                'user_id' => $userId,
                'responsible_user_id' => $respUserId,
                'scheduled_from' => $from,
                'scheduled_until' => $until,
                'status' => $status,
                'notes' => $notes,
            ]);

            $visit->visitors()->attach([1, 2], [
                'checked_in_at' => $from,
                'badge_printed_at' => Carbon::now(),
            ]);
        }
    }
}
