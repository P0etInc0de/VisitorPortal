<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use App\Models\DataRetentionRun;
use App\Models\MonitorSlide;
use App\Models\RecurringVisitSeries;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class PurgeExpiredVisits extends Command
{
    protected $signature = 'visits:purge-expired
        {--dry-run : Count matching records without deleting them}
        {--days= : Override PRIVACY_VISIT_RETENTION_DAYS for this run}
        {--chunk= : Override PRIVACY_PURGE_CHUNK_SIZE for this run}';

    protected $description = 'Purge expired visits and orphaned visitors according to privacy retention settings.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $retentionDays = $this->positiveIntOption('days', (int) config('privacy.visit_retention_days', 365));
        $chunkSize = $this->positiveIntOption('chunk', (int) config('privacy.purge_chunk_size', 500));
        $cutoff = now()->subDays($retentionDays);

        $run = DataRetentionRun::query()->create([
            'command' => 'visits:purge-expired',
            'dry_run' => $dryRun,
            'retention_days' => $retentionDays,
            'cutoff_at' => $cutoff,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $expiredVisits = $this->expiredVisitsQuery($cutoff);
            $expiredVisitIds = (clone $expiredVisits)->pluck('id');
            $visitsMatched = $expiredVisitIds->count();

            $visitsDeleted = $dryRun ? 0 : $this->deleteByChunks($expiredVisits, Visit::class, $chunkSize);

            $expiredRecurringSeries = $this->expiredRecurringSeriesQuery($cutoff);
            $recurringSeriesMatched = (clone $expiredRecurringSeries)->count();
            $recurringSeriesDeleted = $dryRun ? 0 : $this->deleteByChunks($expiredRecurringSeries, RecurringVisitSeries::class, $chunkSize);

            $visitorQuery = $dryRun
                ? $this->potentialOrphanVisitorsQuery($cutoff, $expiredVisitIds)
                : $this->orphanVisitorsQuery($cutoff);
            $expiredVisitorIds = (clone $visitorQuery)->pluck('id');
            $visitorsMatched = $expiredVisitorIds->count();
            $visitorsDeleted = $dryRun ? 0 : $this->deleteByChunks($visitorQuery, Visitor::class, $chunkSize);

            $notificationQuery = $this->expiredNotificationsQuery();
            $notificationsMatched = (clone $notificationQuery)->count();
            $notificationsDeleted = $dryRun ? 0 : $this->deleteByChunks($notificationQuery, DatabaseNotification::class, $chunkSize);

            $expiredAutoMonitorSlides = $this->expiredAutoMonitorSlidesQuery($cutoff);
            $autoMonitorSlidesMatched = (clone $expiredAutoMonitorSlides)->count();
            $autoMonitorSlidesDeleted = $dryRun ? 0 : $this->deleteByChunks($expiredAutoMonitorSlides, MonitorSlide::class, $chunkSize);

            $expiredManualMonitorSlides = $this->expiredManualMonitorSlidesQuery($cutoff);
            $manualMonitorSlidesMatched = (clone $expiredManualMonitorSlides)->count();
            $manualMonitorSlidesAnonymized = $dryRun ? 0 : $this->anonymizeMonitorSlidesByQuery($expiredManualMonitorSlides, $chunkSize);

            $visitorReferenceSlidesMatched = $this->anonymizeMonitorSlideVisitorReferences($expiredVisitorIds, $chunkSize, true);
            $visitorReferenceSlidesAnonymized = $dryRun ? 0 : $this->anonymizeMonitorSlideVisitorReferences($expiredVisitorIds, $chunkSize, false);
            $indefiniteLegalHoldsMatched = (clone $this->indefiniteLegalHoldsQuery())->count();
            $retentionRunLogsDeleted = $dryRun ? 0 : $this->deleteOldRetentionRunLogs($run);

            $run->update([
                'visits_matched' => $visitsMatched,
                'visits_deleted' => $visitsDeleted,
                'visitors_matched' => $visitorsMatched,
                'visitors_deleted' => $visitorsDeleted,
                'notifications_matched' => $notificationsMatched,
                'notifications_deleted' => $notificationsDeleted,
                'monitor_slides_matched' => $autoMonitorSlidesMatched + $manualMonitorSlidesMatched + $visitorReferenceSlidesMatched,
                'monitor_slides_deleted' => $autoMonitorSlidesDeleted,
                'monitor_slides_anonymized' => $manualMonitorSlidesAnonymized + $visitorReferenceSlidesAnonymized,
                'recurring_series_matched' => $recurringSeriesMatched,
                'recurring_series_deleted' => $recurringSeriesDeleted,
                'indefinite_legal_holds_matched' => $indefiniteLegalHoldsMatched,
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            $this->info(sprintf(
                'Retention run completed%s: %d visits, %d recurring series, %d visitors, %d notifications, %d monitor slides matched. %d indefinite legal holds remain. %d old retention run logs deleted.',
                $dryRun ? ' (dry-run)' : '',
                $visitsMatched,
                $recurringSeriesMatched,
                $visitorsMatched,
                $notificationsMatched,
                $autoMonitorSlidesMatched + $manualMonitorSlidesMatched + $visitorReferenceSlidesMatched,
                $indefiniteLegalHoldsMatched,
                $retentionRunLogsDeleted,
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function positiveIntOption(string $option, int $default): int
    {
        $value = $this->option($option);

        return max(1, (int) ($value !== null && $value !== '' ? $value : $default));
    }

    private function expiredVisitsQuery(Carbon $cutoff): Builder
    {
        return Visit::query()
            ->where('scheduled_until', '<', $cutoff)
            // Active legal holds keep visits out of automated deletion.
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereNull('retention_hold_until')
                        ->whereNull('retention_hold_reason');
                })->orWhere(function (Builder $query): void {
                    $query->whereNotNull('retention_hold_until')
                        ->where('retention_hold_until', '<', now());
                });
            });
    }

    private function orphanVisitorsQuery(Carbon $cutoff): Builder
    {
        return Visitor::query()
            ->where('updated_at', '<', $cutoff)
            ->doesntHave('visits');
    }

    /**
     * @param  Collection<int, int>  $expiredVisitIds
     */
    private function potentialOrphanVisitorsQuery(Carbon $cutoff, Collection $expiredVisitIds): Builder
    {
        return Visitor::query()
            ->where('updated_at', '<', $cutoff)
            ->where(function (Builder $query) use ($expiredVisitIds): void {
                $query->doesntHave('visits');

                if ($expiredVisitIds->isNotEmpty()) {
                    $query->orWhere(function (Builder $query) use ($expiredVisitIds): void {
                        $query->whereHas('visits', fn (Builder $query) => $query->whereIn('visits.id', $expiredVisitIds))
                            ->whereDoesntHave('visits', fn (Builder $query) => $query->whereNotIn('visits.id', $expiredVisitIds));
                    });
                }
            });
    }

    private function expiredRecurringSeriesQuery(Carbon $cutoff): Builder
    {
        return RecurringVisitSeries::query()
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('visits', fn (Builder $query) => $query->where('scheduled_until', '>=', $cutoff))
            ->where(function (Builder $query) use ($cutoff): void {
                $query->where(function (Builder $query) use ($cutoff): void {
                    $query->where('ends', RecurringVisitSeries::END_DATE)
                        ->whereNotNull('end_date')
                        ->where('end_date', '<', $cutoff->toDateString());
                })->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->where('ends', RecurringVisitSeries::END_COUNT)
                        ->whereNotNull('generated_until')
                        ->where('generated_until', '<', $cutoff);
                })->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->where('ends', RecurringVisitSeries::END_FOREVER)
                        ->whereNotNull('generated_until')
                        ->where('generated_until', '<', $cutoff);
                })->orWhere(function (Builder $query) use ($cutoff): void {
                    $query->whereDoesntHave('visits')
                        ->where('updated_at', '<', $cutoff);
                });
            });
    }

    private function expiredNotificationsQuery(): Builder
    {
        $days = max(1, (int) config('privacy.notification_retention_days', 365));

        return DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays($days));
    }

    private function expiredAutoMonitorSlidesQuery(Carbon $cutoff): Builder
    {
        return MonitorSlide::query()
            ->where('is_auto_generated', true)
            ->where('updated_at', '<', $cutoff);
    }

    private function expiredManualMonitorSlidesQuery(Carbon $cutoff): Builder
    {
        return MonitorSlide::query()
            ->where('is_auto_generated', false)
            ->where('updated_at', '<', $cutoff)
            ->whereNotNull('visitors');
    }

    private function indefiniteLegalHoldsQuery(): Builder
    {
        return Visit::query()
            ->whereNotNull('retention_hold_reason')
            ->whereNull('retention_hold_until');
    }

    private function deleteOldRetentionRunLogs(DataRetentionRun $currentRun): int
    {
        $days = max(1, (int) config('privacy.run_log_retention_days', 1095));

        return DataRetentionRun::query()
            ->whereKeyNot($currentRun->id)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }

    private function anonymizeMonitorSlidesByQuery(Builder $query, int $chunkSize): int
    {
        $anonymized = 0;

        (clone $query)
            ->select('id')
            ->chunkById($chunkSize, function (Collection $records) use (&$anonymized): void {
                $anonymized += MonitorSlide::query()
                    ->whereKey($records->pluck('id'))
                    ->update(['visitors' => null]);
            });

        return $anonymized;
    }

    /**
     * @param  Collection<int, int>  $visitorIds
     */
    private function anonymizeMonitorSlideVisitorReferences(Collection $visitorIds, int $chunkSize, bool $dryRun): int
    {
        if ($visitorIds->isEmpty()) {
            return 0;
        }

        $visitorIdSet = array_flip($visitorIds->map(fn ($id) => (int) $id)->all());
        $changedSlides = 0;

        MonitorSlide::query()
            ->whereNotNull('visitors')
            ->select(['id', 'visitors'])
            ->chunkById($chunkSize, function (Collection $slides) use ($visitorIdSet, $dryRun, &$changedSlides): void {
                foreach ($slides as $slide) {
                    $visitors = is_array($slide->visitors) ? $slide->visitors : [];
                    $filteredVisitors = collect($visitors)
                        ->reject(fn ($visitor) => is_array($visitor) && isset($visitorIdSet[(int) ($visitor['id'] ?? 0)]))
                        ->values()
                        ->all();

                    if (count($filteredVisitors) === count($visitors)) {
                        continue;
                    }

                    $changedSlides++;

                    if (! $dryRun) {
                        $slide->forceFill(['visitors' => $filteredVisitors === [] ? null : $filteredVisitors])->save();
                    }
                }
            });

        return $changedSlides;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function deleteByChunks(Builder $query, string $modelClass, int $chunkSize): int
    {
        $deleted = 0;

        (clone $query)
            ->select('id')
            ->chunkById($chunkSize, function (Collection $records) use ($modelClass, &$deleted): void {
                $deleted += $modelClass::query()
                    ->whereKey($records->pluck('id'))
                    ->delete();
            });

        return $deleted;
    }
}
