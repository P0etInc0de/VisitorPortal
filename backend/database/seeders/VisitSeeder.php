<?php

namespace Database\Seeders;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedVisit(
            title: 'Technik-Rundgang',
            hostUserId: 2,
            responsibleUserId: 2,
            from: Carbon::now()->setHour(9)->setMinute(30),
            until: Carbon::now()->setHour(11)->setMinute(0),
            status: VisitStatusEnum::Planned->value,
            notes: 'Badge bereitstellen und Empfang briefen',
            participants: [
                1 => [
                    'badge_printed_at' => Carbon::now()->subMinutes(30),
                ],
                2 => [
                    'badge_printed_at' => Carbon::now()->subMinutes(20),
                    'checked_in_at' => Carbon::now()->subMinutes(5),
                    'checked_in_by_user_id' => 2,
                ],
                3 => [],
            ],
        );

        $this->seedVisit(
            title: 'Kundentermin Vertrieb',
            hostUserId: 3,
            responsibleUserId: 2,
            from: Carbon::tomorrow()->setHour(10)->setMinute(15),
            until: Carbon::tomorrow()->setHour(11)->setMinute(15),
            status: VisitStatusEnum::Planned->value,
            notes: 'Termin verschoben, Badge neu vorbereiten',
            participants: [
                4 => [],
            ],
        );
    }

    private function seedVisit(
        string $title,
        int $hostUserId,
        int $responsibleUserId,
        Carbon $from,
        Carbon $until,
        string $status,
        ?string $notes,
        array $participants,
    ): void {
        $visit = Visit::query()->updateOrCreate(
            [
                'title' => $title,
                'scheduled_from' => $from,
            ],
            [
                'user_id' => $hostUserId,
                'responsible_user_id' => $responsibleUserId,
                'scheduled_until' => $until,
                'status' => $status,
                'notes' => $notes,
            ],
        );

        $visit->visitors()->sync($participants);
    }
}
