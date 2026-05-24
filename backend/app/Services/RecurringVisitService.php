<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Services;

use App\Models\RecurringVisitSeries;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecurringVisitService
{
    private const MAX_GENERATION_STEPS = 5000;

    public function createFromPayload(array $payload, User $creator): Visit
    {
        return DB::transaction(function () use ($payload, $creator): Visit {
            $participants = $this->resolveParticipants($payload['participants'] ?? [], $creator);

            if (! $this->recurrenceEnabled($payload)) {
                $visit = $this->createVisit($payload, $creator);
                $this->syncResolvedParticipants($visit, $participants);

                return $visit;
            }

            $series = $this->createSeries($payload, $creator);
            $series->visitors()->sync($participants);

            return $this->generateOccurrences($series, $participants)->first()
                ?? $this->createOccurrence($series, 1, Carbon::parse($payload['scheduled_from']), $participants);
        });
    }

    public function updateFromPayload(Visit $visit, array $payload, User $actor): Visit
    {
        return DB::transaction(function () use ($visit, $payload, $actor): Visit {
            $participants = $this->resolveParticipants($payload['participants'] ?? [], $actor);

            if (! $visit->recurring_visit_series_id) {
                $this->updateVisit($visit, $payload, false);
                $this->syncResolvedParticipants($visit, $participants);

                return $visit->refresh();
            }

            $scope = $payload['recurrence_update_scope'] ?? RecurringVisitSeries::UPDATE_SINGLE;

            if ($scope === RecurringVisitSeries::UPDATE_FUTURE || $scope === RecurringVisitSeries::UPDATE_SERIES) {
                return $this->updateSeriesScope($visit, $payload, $participants, $scope);
            }

            $this->updateVisit($visit, $payload, true);
            $this->syncResolvedParticipants($visit, $participants);

            return $visit->refresh();
        });
    }

    public function fillForeverHorizon(RecurringVisitSeries $series): void
    {
        if ($series->ends !== RecurringVisitSeries::END_FOREVER) {
            return;
        }

        DB::transaction(function () use ($series): void {
            $series->refresh();
            $this->generateOccurrences($series, $this->seriesParticipantSyncPayload($series));
        });
    }

    public function fillAllForeverHorizons(): void
    {
        RecurringVisitSeries::query()
            ->where('ends', RecurringVisitSeries::END_FOREVER)
            ->orderBy('id')
            ->each(fn (RecurringVisitSeries $series) => $this->fillForeverHorizon($series));
    }

    private function createSeries(array $payload, User $creator): RecurringVisitSeries
    {
        $scheduledFrom = Carbon::parse($payload['scheduled_from']);
        $scheduledUntil = Carbon::parse($payload['scheduled_until']);

        return RecurringVisitSeries::query()->create([
            'title' => $payload['title'],
            'site_id' => $this->siteIdFromPayload($payload),
            'host_user_id' => (int) $payload['host_user_id'],
            'substitute_user_id' => $this->nullableUserId($payload['substitute_user_id'] ?? null),
            'created_by_user_id' => $creator->id,
            'status' => $payload['status'],
            'is_confidential' => (bool) ($payload['is_confidential'] ?? false),
            'notes' => $payload['notes'] ?? null,
            'starts_at' => $scheduledFrom,
            'duration_minutes' => max(1, $scheduledFrom->diffInMinutes($scheduledUntil)),
            'frequency' => $payload['recurrence_frequency'],
            'interval_days' => ($payload['recurrence_frequency'] ?? null) === RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS
                ? (int) $payload['recurrence_interval_days']
                : null,
            'ends' => $payload['recurrence_end_type'],
            'end_date' => ($payload['recurrence_end_type'] ?? null) === RecurringVisitSeries::END_DATE
                ? $payload['recurrence_end_date']
                : null,
            'occurrence_count' => ($payload['recurrence_end_type'] ?? null) === RecurringVisitSeries::END_COUNT
                ? (int) $payload['recurrence_occurrence_count']
                : null,
            'exclusion_dates' => $payload['recurrence_exclusion_dates'] ?? [],
        ]);
    }

    /**
     * @param  array<int, array{notes: mixed}>  $participants
     * @return Collection<int, Visit>
     */
    private function generateOccurrences(RecurringVisitSeries $series, array $participants): Collection
    {
        $created = collect();
        $target = $this->generationTarget($series);
        $generatedUntil = $series->generated_until?->copy();
        $lastEvaluated = $generatedUntil;
        $occurrenceNumber = 1;
        $occurrenceStart = $series->starts_at->copy();

        while ($occurrenceNumber <= self::MAX_GENERATION_STEPS) {
            if ($series->ends === RecurringVisitSeries::END_COUNT && $occurrenceNumber > (int) $series->occurrence_count) {
                break;
            }

            if ($target && $occurrenceStart->gt($target)) {
                break;
            }

            $lastEvaluated = $occurrenceStart->copy();

            if (! $generatedUntil || $occurrenceStart->gt($generatedUntil)) {
                $exists = $series->visits()
                    ->where('recurrence_occurrence_number', $occurrenceNumber)
                    ->exists();

                if (! $exists && ! $this->isExcludedDate($series, $occurrenceStart)) {
                    $created->push($this->createOccurrence($series, $occurrenceNumber, $occurrenceStart, $participants));
                }
            }

            $occurrenceNumber++;
            $occurrenceStart = $this->nextOccurrenceStart($occurrenceStart, $series);
        }

        $series->forceFill([
            'generated_until' => $target ?? $lastEvaluated,
        ])->save();

        return $created;
    }

    /**
     * @param  array<int, array{notes: mixed}>  $participants
     */
    private function createOccurrence(RecurringVisitSeries $series, int $occurrenceNumber, Carbon $occurrenceStart, array $participants): Visit
    {
        $visit = Visit::query()->create([
            'recurring_visit_series_id' => $series->id,
            'recurrence_occurrence_number' => $occurrenceNumber,
            'recurrence_original_scheduled_from' => $occurrenceStart,
            'recurrence_is_modified' => false,
            'title' => $series->title,
            'host_user_id' => $series->host_user_id,
            'substitute_user_id' => $series->substitute_user_id,
            'created_by_user_id' => $series->created_by_user_id,
            'site_id' => $series->site_id,
            'scheduled_from' => $occurrenceStart,
            'scheduled_until' => $occurrenceStart->copy()->addMinutes($series->duration_minutes),
            'status' => $series->status,
            'is_confidential' => (bool) $series->is_confidential,
            'notes' => $series->notes,
        ]);

        $this->syncResolvedParticipants($visit, $participants);

        return $visit;
    }

    private function createVisit(array $payload, User $creator): Visit
    {
        return Visit::query()->create([
            'title' => $payload['title'],
            'site_id' => $this->siteIdFromPayload($payload),
            'host_user_id' => (int) $payload['host_user_id'],
            'substitute_user_id' => $this->nullableUserId($payload['substitute_user_id'] ?? null),
            'created_by_user_id' => $creator->id,
            'scheduled_from' => $payload['scheduled_from'],
            'scheduled_until' => $payload['scheduled_until'],
            'status' => $payload['status'],
            'is_confidential' => (bool) ($payload['is_confidential'] ?? false),
            'notes' => $payload['notes'] ?? null,
        ]);
    }

    private function updateVisit(Visit $visit, array $payload, bool $markModified): void
    {
        $visit->update([
            'title' => $payload['title'],
            'site_id' => $this->siteIdFromPayload($payload),
            'host_user_id' => (int) $payload['host_user_id'],
            'substitute_user_id' => $this->nullableUserId($payload['substitute_user_id'] ?? null),
            'scheduled_from' => $payload['scheduled_from'],
            'scheduled_until' => $payload['scheduled_until'],
            'status' => $payload['status'],
            'is_confidential' => (bool) ($payload['is_confidential'] ?? false),
            'notes' => $payload['notes'] ?? null,
            'recurrence_is_modified' => $markModified ? true : $visit->recurrence_is_modified,
        ]);
    }

    /**
     * @param  array<int, array{notes: mixed}>  $participants
     */
    private function updateSeriesScope(Visit $visit, array $payload, array $participants, string $scope): Visit
    {
        $series = $visit->recurringSeries()->lockForUpdate()->firstOrFail();
        $selectedOccurrenceNumber = (int) ($visit->recurrence_occurrence_number ?: 1);
        $selectedStart = Carbon::parse($payload['scheduled_from']);
        $selectedUntil = Carbon::parse($payload['scheduled_until']);
        $durationMinutes = max(1, $selectedStart->diffInMinutes($selectedUntil));
        $frequency = $payload['recurrence_frequency'] ?? $series->frequency;
        $intervalDays = $frequency === RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS
            ? (int) ($payload['recurrence_interval_days'] ?? $series->interval_days ?? 1)
            : null;

        $baseStart = $this->previousOccurrenceStart($selectedStart, $series, $selectedOccurrenceNumber - 1, $frequency, $intervalDays);

        $series->update([
            'title' => $payload['title'],
            'site_id' => $this->siteIdFromPayload($payload),
            'host_user_id' => (int) $payload['host_user_id'],
            'substitute_user_id' => $this->nullableUserId($payload['substitute_user_id'] ?? null),
            'status' => $payload['status'],
            'is_confidential' => (bool) ($payload['is_confidential'] ?? false),
            'notes' => $payload['notes'] ?? null,
            'starts_at' => $baseStart,
            'duration_minutes' => $durationMinutes,
            'frequency' => $frequency,
            'interval_days' => $intervalDays,
            'ends' => $payload['recurrence_end_type'] ?? $series->ends,
            'end_date' => ($payload['recurrence_end_type'] ?? $series->ends) === RecurringVisitSeries::END_DATE
                ? ($payload['recurrence_end_date'] ?? $series->end_date)
                : null,
            'occurrence_count' => ($payload['recurrence_end_type'] ?? $series->ends) === RecurringVisitSeries::END_COUNT
                ? (int) ($payload['recurrence_occurrence_count'] ?? $series->occurrence_count)
                : null,
        ]);

        $series->visitors()->sync($participants);
        $series->refresh();

        $query = $series->visits()
            ->where(function ($query) use ($visit) {
                $query->where('recurrence_is_modified', false)
                    ->orWhere('id', $visit->id);
            });

        if ($scope === RecurringVisitSeries::UPDATE_FUTURE) {
            $query->where('recurrence_occurrence_number', '>=', $selectedOccurrenceNumber);
        }

        $query
            ->orderBy('recurrence_occurrence_number')
            ->get()
            ->each(function (Visit $targetVisit) use ($payload, $participants, $series): void {
                $offset = (int) $targetVisit->recurrence_occurrence_number - 1;
                $occurrenceStart = $this->advanceOccurrenceStart($series->starts_at->copy(), $series, $offset);

                $targetVisit->update([
                    'title' => $payload['title'],
                    'site_id' => $series->site_id,
                    'host_user_id' => (int) $payload['host_user_id'],
                    'substitute_user_id' => $this->nullableUserId($payload['substitute_user_id'] ?? null),
                    'scheduled_from' => $occurrenceStart,
                    'scheduled_until' => $occurrenceStart->copy()->addMinutes($series->duration_minutes),
                    'status' => $payload['status'],
                    'is_confidential' => (bool) ($payload['is_confidential'] ?? false),
                    'notes' => $payload['notes'] ?? null,
                    'recurrence_original_scheduled_from' => $occurrenceStart,
                    'recurrence_is_modified' => false,
                ]);

                $this->syncResolvedParticipants($targetVisit, $participants);
            });

        $this->pruneOutOfRuleOccurrences($series, $scope === RecurringVisitSeries::UPDATE_FUTURE ? $selectedOccurrenceNumber : null);
        $this->generateOccurrences($series, $participants);

        return $visit->refresh();
    }

    private function recurrenceEnabled(array $payload): bool
    {
        return filter_var($payload['recurrence_enabled'] ?? false, FILTER_VALIDATE_BOOL);
    }

    private function siteIdFromPayload(array $payload): int
    {
        return (int) $payload['site_id'];
    }

    private function nullableUserId(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function generationTarget(RecurringVisitSeries $series): ?Carbon
    {
        return match ($series->ends) {
            RecurringVisitSeries::END_DATE => $series->end_date?->copy()->endOfDay(),
            RecurringVisitSeries::END_FOREVER => now()->addMonthsNoOverflow(RecurringVisitSeries::FOREVER_HORIZON_MONTHS),
            default => null,
        };
    }

    private function nextOccurrenceStart(Carbon $occurrenceStart, RecurringVisitSeries $series): Carbon
    {
        return match ($series->frequency) {
            RecurringVisitSeries::FREQUENCY_DAILY => $occurrenceStart->copy()->addDay(),
            RecurringVisitSeries::FREQUENCY_WEEKLY => $occurrenceStart->copy()->addWeek(),
            RecurringVisitSeries::FREQUENCY_MONTHLY => $occurrenceStart->copy()->addMonthNoOverflow(),
            RecurringVisitSeries::FREQUENCY_YEARLY => $occurrenceStart->copy()->addYearNoOverflow(),
            RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS => $occurrenceStart->copy()->addDays(max(1, (int) $series->interval_days)),
            default => $occurrenceStart->copy()->addDay(),
        };
    }

    private function advanceOccurrenceStart(Carbon $start, RecurringVisitSeries $series, int $steps): Carbon
    {
        for ($i = 0; $i < $steps; $i++) {
            $start = $this->nextOccurrenceStart($start, $series);
        }

        return $start;
    }

    private function previousOccurrenceStart(Carbon $start, RecurringVisitSeries $series, int $steps, string $frequency, ?int $intervalDays): Carbon
    {
        for ($i = 0; $i < $steps; $i++) {
            $start = match ($frequency) {
                RecurringVisitSeries::FREQUENCY_DAILY => $start->copy()->subDay(),
                RecurringVisitSeries::FREQUENCY_WEEKLY => $start->copy()->subWeek(),
                RecurringVisitSeries::FREQUENCY_MONTHLY => $start->copy()->subMonthNoOverflow(),
                RecurringVisitSeries::FREQUENCY_YEARLY => $start->copy()->subYearNoOverflow(),
                RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS => $start->copy()->subDays(max(1, (int) $intervalDays)),
                default => $start->copy()->subDay(),
            };
        }

        return $start;
    }

    private function pruneOutOfRuleOccurrences(RecurringVisitSeries $series, ?int $fromOccurrenceNumber): void
    {
        if ($series->ends !== RecurringVisitSeries::END_COUNT && $series->ends !== RecurringVisitSeries::END_DATE) {
            return;
        }

        $series->visits()
            ->where('recurrence_is_modified', false)
            ->when($fromOccurrenceNumber !== null, fn ($query) => $query->where('recurrence_occurrence_number', '>=', $fromOccurrenceNumber))
            ->get()
            ->each(function (Visit $visit) use ($series): void {
                if ($series->ends === RecurringVisitSeries::END_COUNT && $visit->recurrence_occurrence_number > $series->occurrence_count) {
                    $visit->delete();

                    return;
                }

                if ($series->ends === RecurringVisitSeries::END_DATE && $series->end_date && $visit->scheduled_from->gt($series->end_date->copy()->endOfDay())) {
                    $visit->delete();
                }
            });
    }

    private function isExcludedDate(RecurringVisitSeries $series, Carbon $occurrenceStart): bool
    {
        return in_array($occurrenceStart->toDateString(), $series->exclusion_dates ?? [], true);
    }

    /**
     * @return array<int, array{notes: mixed}>
     */
    private function resolveParticipants(array $participantRows, User $actor): array
    {
        $resolved = [];

        foreach ($this->normalizedParticipants(collect($participantRows)) as $row) {
            $visitor = $this->resolveVisitor($row, $actor);
            $resolved[$visitor->id] = [
                'notes' => Arr::get($row, 'notes'),
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<int, array{notes: mixed}>  $participants
     */
    private function syncResolvedParticipants(Visit $visit, array $participants): void
    {
        $visit->visitors()->sync($participants);
    }

    /**
     * @return array<int, array{notes: mixed}>
     */
    private function seriesParticipantSyncPayload(RecurringVisitSeries $series): array
    {
        return $series->visitors()
            ->get()
            ->mapWithKeys(fn (Visitor $visitor): array => [
                $visitor->id => ['notes' => $visitor->pivot?->notes],
            ])
            ->all();
    }

    private function normalizedParticipants(Collection $participantRows): Collection
    {
        return $participantRows
            ->map(function ($row) {
                return [
                    'visitor_id' => Arr::get($row, 'visitor_id') ?: null,
                    'title' => $this->normalizeText(Arr::get($row, 'title')),
                    'first_name' => $this->normalizeText(Arr::get($row, 'first_name')),
                    'name' => $this->normalizeText(Arr::get($row, 'name')),
                    'email' => $this->normalizeText(Arr::get($row, 'email')),
                    'phone' => $this->normalizeText(Arr::get($row, 'phone')),
                    'company' => $this->normalizeText(Arr::get($row, 'company')),
                    'notes' => $this->normalizeText(Arr::get($row, 'notes')),
                ];
            })
            ->filter(function (array $row) {
                return filled($row['visitor_id'])
                    || filled($row['title'])
                    || filled($row['first_name'])
                    || filled($row['name'])
                    || filled($row['email'])
                    || filled($row['phone'])
                    || filled($row['company']);
            })
            ->filter(function (array $row) {
                if (filled($row['visitor_id'])) {
                    return true;
                }

                return filled($row['first_name']) && filled($row['name']);
            })
            ->values();
    }

    private function resolveVisitor(array $row, User $actor): Visitor
    {
        if (filled($row['visitor_id'])) {
            return Visitor::query()
                ->visibleTo($actor)
                ->findOrFail($row['visitor_id']);
        }

        $existingByEmail = filled($row['email'])
            ? Visitor::query()->visibleTo($actor)->where('email', $row['email'])->first()
            : null;
        $existingVisitor = $existingByEmail;

        if ($existingVisitor) {
            if (! $this->canUpdateExistingVisitor($actor, $existingVisitor)) {
                return $existingVisitor;
            }

            $existingVisitor->update([
                'title' => $row['title'] ?: $existingVisitor->title,
                'first_name' => $row['first_name'] ?: $existingVisitor->first_name,
                'name' => $row['name'] ?: $existingVisitor->name,
                'email' => $row['email'] ?: $existingVisitor->email,
                'phone' => $row['phone'] ?: $existingVisitor->phone,
                'company' => $row['company'] ?: $existingVisitor->company,
            ]);

            return $existingVisitor;
        }

        return Visitor::query()->create([
            'title' => $row['title'],
            'first_name' => $row['first_name'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'company' => $row['company'],
            'notes' => null,
            'created_by_user_id' => $actor->id,
        ]);
    }

    private function canUpdateExistingVisitor(User $actor, Visitor $visitor): bool
    {
        return $actor->can('update', $visitor) || $actor->can('EditAny:Visitor');
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
