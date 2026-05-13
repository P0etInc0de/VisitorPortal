<?php

namespace Database\Seeders;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now()->setSecond(0);
        $today = $now->copy()->startOfDay();
        $reception = $this->user('empfang@demo.local');
        $admin = $this->user('admin@demo.local');

        $this->seedVisit([
            'title' => 'Morning Pages Stand-up',
            'host' => 'jo.march@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->setTime(8, 30),
            'until' => $today->copy()->setTime(9, 0),
            'status' => VisitStatusEnum::Completed,
            'notes' => 'Kurz, freundlich, mit sehr gutem Protokoll.',
        ], [
            'jo.march@orchard.example' => [
                'badge_printed_at' => $today->copy()->setTime(8, 10),
                'checked_in_at' => $today->copy()->setTime(8, 22),
                'checked_in_by_user_id' => $reception->id,
                'checked_out_at' => $today->copy()->setTime(9, 5),
                'checked_out_by_user_id' => $reception->id,
            ],
            'anne.shirley@green-gables.example' => [
                'checked_in_at' => $today->copy()->setTime(8, 25),
                'checked_in_by_user_id' => $reception->id,
                'checked_out_at' => $today->copy()->setTime(9, 3),
                'checked_out_by_user_id' => $reception->id,
            ],
        ]);

        $this->seedVisit([
            'title' => 'Back-to-back 1: Research Check-in',
            'host' => 'hermione.granger@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->setTime(9, 0),
            'until' => $today->copy()->setTime(9, 30),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Dieselbe Person hat direkt danach weitere Termine und muss aktuell je Termin separat eingecheckt werden.',
        ], ['hermione.granger@hogwarts.example']);

        $this->seedVisit([
            'title' => 'Back-to-back 2: Library Safety Review',
            'host' => 'matilda.wormwood@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->setTime(9, 30),
            'until' => $today->copy()->setTime(10, 0),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Zweiter Termin mit derselben Besucherin direkt im Anschluss.',
        ], ['hermione.granger@hogwarts.example']);

        $this->seedVisit([
            'title' => 'Back-to-back 3: Time-Turner Debrief',
            'host' => 'arthur.dent@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->setTime(10, 0),
            'until' => $today->copy()->setTime(10, 30),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Dritter Termin mit derselben Besucherin. Guter Edge Case für Check-in/out.',
        ], ['hermione.granger@hogwarts.example']);

        $this->seedVisit([
            'title' => 'Toto prüft den Empfangsteppich',
            'host' => 'samwise.gamgee@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->setTime(11, 0),
            'until' => $today->copy()->setTime(11, 45),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Hund ist eingecheckt. Bitte Wassernapf nicht vergessen.',
        ], [
            'toto.gale@oz.example' => [
                'badge_printed_at' => $now->copy()->subMinutes(35),
                'checked_in_at' => $now->copy()->subMinutes(25),
                'checked_in_by_user_id' => $reception->id,
                'notes' => 'Vier Pfoten, ein Ausweis.',
            ],
        ]);

        $this->seedVisit([
            'title' => 'Council of Many Pages',
            'host' => 'bilbo.baggins@demo.local',
            'responsible' => 'lucy.pevensie@demo.local',
            'from' => $today->copy()->setTime(14, 0),
            'until' => $today->copy()->setTime(16, 0),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Großer Termin mit 24 Gästen. Testet Listen, Ausweise und Dashboard-Zähler.',
        ], $this->firstVisitorEmails(24));

        $this->seedVisit([
            'title' => 'Rabbit Hole Route Planning',
            'host' => 'lucy.pevensie@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->addDay()->setTime(10, 0),
            'until' => $today->copy()->addDay()->setTime(11, 0),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Morgen, damit die Check-In/Out-Seite den 48h-Zeitraum zeigt.',
        ], ['alice.liddell@wonder.example', 'dorothy.gale@oz.example']);

        $this->seedVisit([
            'title' => 'Draft: Chocolate Prototype Tasting',
            'host' => 'anne.shirley@demo.local',
            'responsible' => 'admin@demo.local',
            'from' => $today->copy()->addDays(2)->setTime(13, 30),
            'until' => $today->copy()->addDays(2)->setTime(14, 30),
            'status' => VisitStatusEnum::Draft,
            'notes' => 'Entwurf, weil die Muster noch nicht freigegeben sind.',
        ], ['willy.wonka@chocolate.example', 'pippi.langstrumpf@villa.example']);

        $this->seedVisit([
            'title' => 'Canceled: Storm over Thornfield',
            'host' => 'atticus.finch@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $today->copy()->subDay()->setTime(15, 0),
            'until' => $today->copy()->subDay()->setTime(16, 0),
            'status' => VisitStatusEnum::Canceled,
            'notes' => 'Abgesagt wegen Wetterwarnung.',
            'canceled_at' => $today->copy()->subDay()->setTime(8, 15),
            'canceled_by_user_id' => $admin->id,
        ], ['jane.eyre@thornfield.example']);

        $this->seedVisit([
            'title' => 'Walk-in: Very Prepared Nanny',
            'host' => 'elizabeth.bennet@demo.local',
            'responsible' => 'empfang@demo.local',
            'from' => $now->copy()->subMinutes(10),
            'until' => $now->copy()->addMinutes(50),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Als Walk-in am Empfang erfasst.',
            'is_walk_in' => true,
        ], [
            'mary.poppins@cherry.example' => [
                'checked_in_at' => $now->copy()->subMinutes(9),
                'checked_in_by_user_id' => $reception->id,
                'notes' => 'Walk-in am Empfang registriert.',
            ],
        ]);

        $this->seedVisit([
            'title' => 'Far Future: Improbability Audit',
            'host' => 'arthur.dent@demo.local',
            'responsible' => 'admin@demo.local',
            'from' => $today->copy()->addDays(35)->setTime(12, 0),
            'until' => $today->copy()->addDays(35)->setTime(13, 0),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Weiter als 48 Stunden entfernt, sollte nicht im Check-In/Out-Board auftauchen.',
        ], ['arthur.dent@galaxy.example', 'ford.prefect@galaxy.example']);

        $this->command?->info('VisitSeeder ausgeführt: Demo-Besuche inklusive Back-to-back- und 24-Gäste-Edge-Cases angelegt/aktualisiert.');
    }

    /**
     * @param  array<string, mixed>  $visitData
     * @param  array<int|string, string|array<string, mixed>>  $visitors
     */
    private function seedVisit(array $visitData, array $visitors): Visit
    {
        $host = $this->user($visitData['host']);
        $responsible = $this->user($visitData['responsible']);

        $visit = Visit::query()->updateOrCreate(
            ['title' => $visitData['title']],
            [
                'user_id' => $host->id,
                'responsible_user_id' => $responsible->id,
                'scheduled_from' => $visitData['from'],
                'scheduled_until' => $visitData['until'],
                'status' => $visitData['status']->value,
                'is_walk_in' => (bool) ($visitData['is_walk_in'] ?? false),
                'notes' => $visitData['notes'] ?? null,
                'canceled_at' => $visitData['canceled_at'] ?? null,
                'canceled_by_user_id' => $visitData['canceled_by_user_id'] ?? null,
            ]
        );

        $syncPayload = [];

        foreach ($visitors as $email => $pivot) {
            if (is_int($email)) {
                $email = $pivot;
                $pivot = [];
            }

            $syncPayload[$this->visitor($email)->id] = $pivot;
        }

        $visit->visitors()->sync($syncPayload);

        return $visit;
    }

    /**
     * @return array<int, string>
     */
    private function firstVisitorEmails(int $count): array
    {
        return Visitor::query()
            ->orderBy('id')
            ->limit($count)
            ->pluck('email')
            ->all();
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    private function visitor(string $email): Visitor
    {
        return Visitor::query()->where('email', $email)->firstOrFail();
    }
}
