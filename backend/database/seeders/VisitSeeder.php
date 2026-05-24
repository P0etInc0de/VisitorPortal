<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VisitSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo visits are disabled in production.');
        }

        $faker = FakerFactory::create('en_US');
        $faker->seed(20260516);

        $now = Carbon::now()->setSecond(0);
        $today = $now->copy()->startOfDay();
        $reception = $this->user('reception@example.org');
        $security = $this->user('security@example.org');
        $admin = $this->user('admin@example.org');

        $this->seedVisit([
            'title' => 'Morning operations briefing',
            'host' => 'employee@example.org',
            'substitute' => 'reception@example.org',
            'from' => $now->copy()->subHours(3),
            'until' => $now->copy()->subHours(2),
            'status' => VisitStatusEnum::Completed,
            'notes' => 'Completed demo visit with check-in and check-out timestamps.',
        ], [
            'alice.reed@example.com' => [
                'badge_printed_at' => $now->copy()->subHours(3)->subMinutes(20),
                'checked_in_at' => $now->copy()->subHours(3)->subMinutes(10),
                'checked_in_by_user_id' => $reception->id,
                'checked_out_at' => $now->copy()->subHours(2)->addMinutes(4),
                'checked_out_by_user_id' => $reception->id,
            ],
            'dorothy.lane@example.org' => [
                'checked_in_at' => $now->copy()->subHours(3)->subMinutes(8),
                'checked_in_by_user_id' => $reception->id,
                'checked_out_at' => $now->copy()->subHours(2)->addMinutes(2),
                'checked_out_by_user_id' => $reception->id,
            ],
        ]);

        $this->seedVisit([
            'title' => 'Supplier onboarding review',
            'host' => 'admin@example.org',
            'substitute' => 'reception@example.org',
            'from' => $now->copy()->addHour(),
            'until' => $now->copy()->addHours(2),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Ada Avery demo visit for dashboard, reception, and check-in workflows.',
        ], ['clara.bennett@example.net']);

        $this->seedVisit([
            'title' => 'Engineering partner workshop',
            'host' => 'admin@example.org',
            'substitute' => 'security@example.org',
            'from' => $now->copy()->addHours(4),
            'until' => $now->copy()->addHours(5)->addMinutes(30),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Ada Avery multi-participant planned visit.',
        ], ['arthur.blake@example.com', 'emily.wright@example.org']);

        $this->seedVisit([
            'title' => 'Logistics planning session',
            'host' => 'demo.user01@example.org',
            'substitute' => 'reception@example.org',
            'from' => $now->copy()->addMinutes(90),
            'until' => $now->copy()->addMinutes(135),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Single visitor planned visit.',
        ], ['john.spencer@example.net']);

        $this->seedVisit([
            'title' => 'Manufacturing quality review',
            'host' => 'demo.user09@example.net',
            'substitute' => 'reception@example.org',
            'from' => $now->copy()->addHours(6),
            'until' => $now->copy()->addHours(8),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Larger group visit for dashboards and monitor pagination.',
        ], $this->firstVisitorEmails(10));

        $this->seedVisit([
            'title' => 'Walk-in: documentation pickup',
            'host' => 'employee@example.org',
            'created_by' => 'security@example.org',
            'from' => $now->copy()->subMinutes(10),
            'until' => $now->copy()->addMinutes(50),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Walk-in captured at reception.',
            'is_walk_in' => true,
        ], [
            'jane.miller@example.com' => [
                'checked_in_at' => $now->copy()->subMinutes(9),
                'checked_in_by_user_id' => $security->id,
                'notes' => 'Walk-in registered at the front desk.',
            ],
        ]);

        $this->seedVisit([
            'title' => 'Safety audit preparation',
            'host' => 'admin@example.org',
            'substitute' => 'reception@example.org',
            'from' => $now->copy()->addHours(22),
            'until' => $now->copy()->addHours(23),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'Ada Avery visit for next-day reception board coverage.',
        ], ['thomas.green@example.org', 'lucy.walker@example.net']);

        $this->seedVisit([
            'title' => 'Draft: component roadmap',
            'host' => 'demo.user03@example.net',
            'substitute' => 'admin@example.org',
            'from' => $today->copy()->addDays(2)->setTime(13, 30),
            'until' => $today->copy()->addDays(2)->setTime(14, 30),
            'status' => VisitStatusEnum::Draft,
            'notes' => 'Draft visits are intentionally excluded from automatic monitor generation.',
        ], ['walter.brooks@example.com']);

        $this->seedVisit([
            'title' => 'Canceled: facility walkthrough',
            'host' => 'demo.user06@example.net',
            'substitute' => 'reception@example.org',
            'from' => $today->copy()->subDay()->setTime(15, 0),
            'until' => $today->copy()->subDay()->setTime(16, 0),
            'status' => VisitStatusEnum::Canceled,
            'notes' => 'Canceled demo visit.',
            'canceled_at' => $today->copy()->subDay()->setTime(8, 15),
            'canceled_by_user_id' => $admin->id,
        ], ['visitor01@example.org']);

        $this->seedVisit([
            'title' => 'Far future systems audit',
            'host' => 'admin@example.org',
            'substitute' => 'security@example.org',
            'from' => $today->copy()->addDays(35)->setTime(12, 0),
            'until' => $today->copy()->addDays(35)->setTime(13, 0),
            'status' => VisitStatusEnum::Planned,
            'notes' => 'More than 48 hours away, should stay out of the check-in board.',
        ], ['visitor02@example.com', 'visitor03@example.net']);

        foreach ($this->fakerVisits($faker, 62, $today, $admin) as [$visitData, $visitorEmails]) {
            $this->seedVisit($visitData, $visitorEmails);
        }

        $this->command?->info('VisitSeeder ausgeführt: 72 Demo-Besuche angelegt/aktualisiert.');
    }

    /**
     * @return array<int, array{array<string, mixed>, array<int, string>}>
     */
    private function fakerVisits(Generator $faker, int $count, Carbon $today, User $cancelingUser): array
    {
        $hostEmails = User::query()
            ->where('email', '!=', 'welcome@example.org')
            ->orderBy('id')
            ->pluck('email')
            ->values();
        $substituteEmails = collect(['reception@example.org', 'security@example.org', 'admin@example.org']);
        $visitorEmails = Visitor::query()->orderBy('id')->pluck('email')->values();
        $topics = ['planning', 'quality', 'operations', 'safety', 'prototype', 'supplier', 'training', 'logistics'];
        $visits = [];

        for ($index = 1; $index <= $count; $index++) {
            $dayOffset = ($index % 46) - 5;
            $startHour = 8 + ($index % 9);
            $startsAt = $today->copy()->addDays($dayOffset)->setTime($startHour, ($index % 2) * 30);
            $status = VisitStatusEnum::Planned;

            if ($index % 11 === 0) {
                $status = VisitStatusEnum::Canceled;
            } elseif ($index % 8 === 0) {
                $status = VisitStatusEnum::Draft;
            } elseif ($startsAt->isPast() && $index % 5 === 0) {
                $status = VisitStatusEnum::Completed;
            }

            $participantCount = 1 + ($index % 4);
            $participants = [];

            for ($participant = 0; $participant < $participantCount; $participant++) {
                $participants[] = $visitorEmails[($index + $participant) % $visitorEmails->count()];
            }

            $hostEmail = $hostEmails[$index % $hostEmails->count()];
            $substituteEmail = $substituteEmails[$index % $substituteEmails->count()];

            if ($substituteEmail === $hostEmail) {
                $substituteEmail = $substituteEmails[($index + 1) % $substituteEmails->count()];
            }

            $visitData = [
                'title' => sprintf('Demo %02d: %s %s', $index, ucfirst($topics[$index % count($topics)]), $faker->randomElement(['review', 'briefing', 'workshop', 'check-in'])),
                'host' => $hostEmail,
                'substitute' => $substituteEmail,
                'from' => $startsAt,
                'until' => $startsAt->copy()->addMinutes(45 + (($index % 3) * 15)),
                'status' => $status,
                'notes' => $faker->sentence(8),
                'is_confidential' => $index % 13 === 0,
            ];

            if ($status === VisitStatusEnum::Canceled) {
                $visitData['canceled_at'] = $startsAt->copy()->subDay();
                $visitData['canceled_by_user_id'] = $cancelingUser->id;
            }

            $visits[] = [$visitData, $participants];
        }

        return $visits;
    }

    /**
     * @param  array<string, mixed>  $visitData
     * @param  array<int|string, string|array<string, mixed>>  $visitors
     */
    private function seedVisit(array $visitData, array $visitors): Visit
    {
        $host = $this->user($visitData['host']);
        $substitute = isset($visitData['substitute']) ? $this->user($visitData['substitute']) : null;
        $createdBy = isset($visitData['created_by']) ? $this->user($visitData['created_by']) : $host;

        $visit = Visit::query()->updateOrCreate(
            ['title' => $visitData['title']],
            [
                'site_id' => $host->site_id,
                'host_user_id' => $host->id,
                'substitute_user_id' => $substitute?->id,
                'created_by_user_id' => $createdBy->id,
                'scheduled_from' => $visitData['from'],
                'scheduled_until' => $visitData['until'],
                'status' => $visitData['status']->value,
                'is_confidential' => (bool) ($visitData['is_confidential'] ?? false),
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
