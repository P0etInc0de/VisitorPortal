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
        $this->createOrUpdateVisit(
            1,
            2,
            Carbon::now()->subMonths(2)->setHour(10)->setMinute(0),
            Carbon::now()->subMonths(2)->setHour(11)->setMinute(0),
            'Strategie-Review',
            VisitStatusEnum::Completed,
            'Strategie-Review',
            [1, 3]
        );

        $this->createOrUpdateVisit(
            2,
            1,
            Carbon::now()->subDays(10)->setHour(14)->setMinute(0),
            Carbon::now()->subDays(10)->setHour(15)->setMinute(30),
            'Meeting',
            VisitStatusEnum::Completed,
            'Kurzfristiges Abstimmungsmeeting',
            [2, 6]
        );

        $this->createOrUpdateVisit(
            3,
            3,
            Carbon::yesterday()->setHour(9)->setMinute(0),
            Carbon::yesterday()->setHour(10)->setMinute(0),
            'Expandierung nach Teheran',
            VisitStatusEnum::Canceled,
            'Termin kurzfristig ausgefallen',
            [4, 5]
        );

        $this->createOrUpdateVisit(
            1,
            2,
            Carbon::now()->addDays(1)->setHour(15)->setMinute(0),
            Carbon::now()->addDays(1)->setHour(16)->setMinute(0),
            'FROG',
            VisitStatusEnum::Planned,
            'Firmen Rundgang Ohne Grund',
            [2, 4]
        );

        $this->createOrUpdateVisit(
            1,
            1,
            Carbon::now()->addDays(7)->setHour(13)->setMinute(0),
            Carbon::now()->addDays(7)->setHour(14)->setMinute(0),
            'Weltherrschaft: Projekt-Kickoff',
            VisitStatusEnum::Planned,
            'Kickoff neues Projekt',
            [1]
        );

        $this->createOrUpdateVisit(
            1,
            1,
            Carbon::now()->addDays(7)->setHour(15)->setMinute(0),
            Carbon::now()->addDays(7)->setHour(16)->setMinute(0),
            'Kaffeekränzchen',
            VisitStatusEnum::Planned,
            'Kaffee und Tee',
            [1, 5, 6]
        );

        $this->createOrUpdateVisit(
            2,
            2,
            Carbon::now()->addMonth()->addDays(10)->setHour(11)->setMinute(0),
            Carbon::now()->addMonth()->addDays(10)->setHour(12)->setMinute(30),
            'Analyse-/Planungsmeeting',
            VisitStatusEnum::Draft,
            'Deep Dive Analyse und Planung',
            [2, 3, 6]
        );

        $this->createOrUpdateVisit(
            2,
            1,
            Carbon::now()->addMonths(3)->setHour(15)->setMinute(0),
            Carbon::now()->addMonths(3)->setHour(16)->setMinute(0),
            'Quartalsmeeting',
            VisitStatusEnum::Canceled,
            'Quartalsmeeting mit allen Stakeholdern',
            [4, 1]
        );

        $this->createOrUpdateVisit(
            1,
            3,
            Carbon::now()->addYear()->setHour(10)->setMinute(0),
            Carbon::now()->addYear()->setHour(11)->setMinute(30),
            'Strategieplanung',
            VisitStatusEnum::Planned,
            'Jahresstrategie 2027 Planung',
            [5, 6, 3]
        );
    }

    private function createOrUpdateVisit(int $userId, int $respUserId, Carbon $from, Carbon $until, string $title, VisitStatusEnum $status, ?string $notes, array $visitorIds = []): void
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
                'title' => $title,
                'status' => $status,
                'notes' => $notes,
            ]);

            $visit->visitors()->sync($visitorIds);
        }
    }
}
