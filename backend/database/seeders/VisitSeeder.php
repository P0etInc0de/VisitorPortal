<?php

namespace Database\Seeders;

use App\Models\Visit;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class VisitSeeder extends Seeder
{
    /**
     * Seeder for visits table
     */
    public function run(): void
    {
        $this->createVisit(1, 2, Carbon::now()->setHour(14)->setMinute(0), Carbon::now()->setHour(16)->setMinute(0), 'planned', 'Forza Meeting');
        $this->createVisit(2, 1, Carbon::yesterday()->setHour(10)->setMinute(0), Carbon::yesterday()->setHour(11)->setMinute(30), 'completed', 'Piratenpizza im Mountainbikepark');
    }

    private function createVisit(int $userId, int $respUserId, Carbon $from, Carbon $until, string $status, ?string $notes): void
    {
        Visit::forceCreate([
            'user_id'             => $userId,
            'responsible_user_id' => $respUserId,
            'scheduled_from'      => $from,
            'scheduled_until'     => $until,
            'status'              => $status,
            'notes'               => $notes,
        ]);
    }
}
