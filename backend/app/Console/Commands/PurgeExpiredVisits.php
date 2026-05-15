<?php

namespace App\Console\Commands;

use App\Models\DataRetentionRun;
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

            $visitorQuery = $dryRun
                ? $this->potentialOrphanVisitorsQuery($cutoff, $expiredVisitIds)
                : $this->orphanVisitorsQuery($cutoff);
            $visitorsMatched = (clone $visitorQuery)->count();
            $visitorsDeleted = $dryRun ? 0 : $this->deleteByChunks($visitorQuery, Visitor::class, $chunkSize);

            $notificationQuery = $this->expiredNotificationsQuery();
            $notificationsMatched = (clone $notificationQuery)->count();
            $notificationsDeleted = $dryRun ? 0 : $this->deleteByChunks($notificationQuery, DatabaseNotification::class, $chunkSize);

            $run->update([
                'visits_matched' => $visitsMatched,
                'visits_deleted' => $visitsDeleted,
                'visitors_matched' => $visitorsMatched,
                'visitors_deleted' => $visitorsDeleted,
                'notifications_matched' => $notificationsMatched,
                'notifications_deleted' => $notificationsDeleted,
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            $this->info(sprintf(
                'Retention run completed%s: %d visits, %d visitors, %d notifications matched.',
                $dryRun ? ' (dry-run)' : '',
                $visitsMatched,
                $visitorsMatched,
                $notificationsMatched,
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

    private function expiredNotificationsQuery(): Builder
    {
        $days = max(1, (int) config('privacy.notification_retention_days', 365));

        return DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays($days));
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
