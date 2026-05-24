<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Visit;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Tasks\CompleteFinishedVisits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CompleteFinishedVisitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_past_planned_visit_is_completed_after_checked_in_participants_checked_out(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 22, 12, 0, 0, config('app.timezone')));

        try {
            $visit = Visit::factory()->create([
                'scheduled_from' => now()->subHours(3),
                'scheduled_until' => now()->subHour(),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $visitors = Visitor::factory()->count(3)->create();

            $this->attachParticipantState($visit, $visitors[0], now()->subHours(2), now()->subMinutes(90));
            $this->attachParticipantState($visit, $visitors[1], now()->subHours(2), now()->subMinutes(90));
            $this->attachParticipantState($visit, $visitors[2], null, null);

            $completed = (new CompleteFinishedVisits)();

            $this->assertSame(1, $completed);
            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'status' => VisitStatusEnum::Completed->value,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_future_finished_visit_remains_planned_until_the_appointment_ends(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 22, 12, 0, 0, config('app.timezone')));

        try {
            $visit = Visit::factory()->create([
                'scheduled_from' => now()->subHour(),
                'scheduled_until' => now()->addHour(),
                'status' => VisitStatusEnum::Planned->value,
            ]);
            $visitor = Visitor::factory()->create();
            $this->attachParticipantState($visit, $visitor, now()->subMinutes(45), now()->subMinutes(15));

            $completed = (new CompleteFinishedVisits)();

            $this->assertSame(0, $completed);
            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'status' => VisitStatusEnum::Planned->value,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_past_visit_with_active_checked_in_participant_remains_planned(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 22, 12, 0, 0, config('app.timezone')));

        try {
            $activeVisit = Visit::factory()->create([
                'scheduled_from' => now()->subHours(3),
                'scheduled_until' => now()->subHour(),
                'status' => VisitStatusEnum::Planned->value,
            ]);

            $this->attachParticipantState($activeVisit, Visitor::factory()->create(), now()->subHours(2), null);

            $completed = (new CompleteFinishedVisits)();

            $this->assertSame(0, $completed);
            $this->assertDatabaseHas('visits', [
                'id' => $activeVisit->id,
                'status' => VisitStatusEnum::Planned->value,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_canceled_visit_is_not_completed_automatically(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 22, 12, 0, 0, config('app.timezone')));

        try {
            $canceledBy = User::factory()->create();
            $visit = Visit::factory()->create([
                'scheduled_from' => now()->subHours(3),
                'scheduled_until' => now()->subHour(),
                'status' => VisitStatusEnum::Canceled->value,
                'canceled_at' => now()->subHours(4),
                'canceled_by_user_id' => $canceledBy->id,
            ]);
            $this->attachParticipantState($visit, Visitor::factory()->create(), now()->subHours(2), now()->subMinutes(90));

            $completed = (new CompleteFinishedVisits)();

            $this->assertSame(0, $completed);
            $this->assertDatabaseHas('visits', [
                'id' => $visit->id,
                'status' => VisitStatusEnum::Canceled->value,
                'canceled_by_user_id' => $canceledBy->id,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function attachParticipantState(Visit $visit, Visitor $visitor, ?Carbon $checkedInAt, ?Carbon $checkedOutAt): void
    {
        $visit->visitors()->attach($visitor->id, [
            'checked_in_at' => $checkedInAt,
            'checked_out_at' => $checkedOutAt,
        ]);
    }
}
