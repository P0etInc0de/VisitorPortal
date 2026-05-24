<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use SplFileInfo;

class PurgeTechnicalData extends Command
{
    protected $signature = 'privacy:purge-technical-data
        {--dry-run : Count matching technical records without deleting them}';

    protected $description = 'Purge technical data according to privacy retention settings.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! (bool) config('privacy.technical_retention.enabled', true)) {
            $this->info('Technical privacy purge skipped: disabled.');

            return self::SUCCESS;
        }

        $this->info('Technical privacy purge '.($dryRun ? 'dry-run' : 'started').'.');

        foreach ($this->purgeCategories($dryRun) as $result) {
            $this->line(sprintf(
                '%s: status=%s, matched=%d, deleted=%d',
                $result['category'],
                $result['status'],
                $result['matched'],
                $result['deleted'],
            ));
        }

        $this->info('Technical privacy purge completed'.($dryRun ? ' (dry-run).' : '.'));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{category: string, status: string, matched: int, deleted: int}>
     */
    private function purgeCategories(bool $dryRun): array
    {
        return [
            $this->purgeSessions($dryRun),
            $this->purgeFailedJobs($dryRun),
            $this->purgeJobBatches($dryRun),
            $this->purgeDailyLogs($dryRun),
            $this->purgeExpiredHealthCache($dryRun),
        ];
    }

    /**
     * @return array{category: string, status: string, matched: int, deleted: int}
     */
    private function purgeSessions(bool $dryRun): array
    {
        if (config('session.driver') !== 'database') {
            return $this->result('sessions', 'skipped:unsupported-session-driver');
        }

        $connectionName = config('session.connection');
        $table = (string) config('session.table', 'sessions');

        if (! $this->tableExists($connectionName, $table)) {
            return $this->result('sessions', 'skipped:missing-table');
        }

        $cutoff = now()->subDays($this->retentionDays('session_days'))->timestamp;
        $query = $this->connection($connectionName)->table($table)
            ->where('last_activity', '<', $cutoff);
        $matched = (clone $query)->count();
        $deleted = $dryRun ? 0 : (clone $query)->delete();

        return $this->result('sessions', 'checked', $matched, $deleted);
    }

    /**
     * @return array{category: string, status: string, matched: int, deleted: int}
     */
    private function purgeFailedJobs(bool $dryRun): array
    {
        $connectionName = config('queue.failed.database');
        $table = (string) config('queue.failed.table', 'failed_jobs');

        if (! $this->tableExists($connectionName, $table)) {
            return $this->result('failed_jobs', 'skipped:missing-table');
        }

        $cutoff = now()->subDays($this->retentionDays('failed_job_days'));
        $query = $this->connection($connectionName)->table($table)
            ->where('failed_at', '<', $cutoff);
        $matched = (clone $query)->count();
        $deleted = $dryRun ? 0 : (clone $query)->delete();

        return $this->result('failed_jobs', 'checked', $matched, $deleted);
    }

    /**
     * @return array{category: string, status: string, matched: int, deleted: int}
     */
    private function purgeJobBatches(bool $dryRun): array
    {
        $connectionName = config('queue.batching.database');
        $table = (string) config('queue.batching.table', 'job_batches');

        if (! $this->tableExists($connectionName, $table)) {
            return $this->result('job_batches', 'skipped:missing-table');
        }

        $cutoff = now()->subDays($this->retentionDays('job_batch_days'))->timestamp;
        $query = $this->connection($connectionName)->table($table)
            ->where(function ($query) use ($cutoff): void {
                $query->where(function ($query) use ($cutoff): void {
                    $query->whereNotNull('finished_at')
                        ->where('finished_at', '<', $cutoff);
                })->orWhere(function ($query) use ($cutoff): void {
                    $query->whereNull('finished_at')
                        ->whereNotNull('cancelled_at')
                        ->where('cancelled_at', '<', $cutoff);
                })->orWhere(function ($query) use ($cutoff): void {
                    $query->whereNull('finished_at')
                        ->whereNull('cancelled_at')
                        ->where('created_at', '<', $cutoff);
                });
            });
        $matched = (clone $query)->count();
        $deleted = $dryRun ? 0 : (clone $query)->delete();

        return $this->result('job_batches', 'checked', $matched, $deleted);
    }

    /**
     * @return array{category: string, status: string, matched: int, deleted: int}
     */
    private function purgeDailyLogs(bool $dryRun): array
    {
        $logPath = $this->safeLogPath();

        if (! is_dir($logPath)) {
            return $this->result('daily_logs', 'skipped:missing-directory');
        }

        $cutoff = now()->subDays($this->retentionDays('log_days'));
        $files = collect(File::allFiles($logPath))
            ->filter(fn (SplFileInfo $file): bool => $this->isPurgeableDailyLog($file, $logPath, $cutoff))
            ->values();
        $matched = $files->count();
        $deleted = 0;

        if (! $dryRun) {
            foreach ($files as $file) {
                if (@unlink($file->getPathname())) {
                    $deleted++;
                }
            }
        }

        return $this->result('daily_logs', 'checked', $matched, $deleted);
    }

    /**
     * @return array{category: string, status: string, matched: int, deleted: int}
     */
    private function purgeExpiredHealthCache(bool $dryRun): array
    {
        $storeName = (string) config('health.cache_store', config('cache.default'));
        $storeConfig = config("cache.stores.{$storeName}");

        if (! is_array($storeConfig) || ($storeConfig['driver'] ?? null) !== 'database') {
            return $this->result('health_cache', 'skipped:ttl-managed-store');
        }

        $connectionName = $storeConfig['connection'] ?? null;
        $table = (string) ($storeConfig['table'] ?? 'cache');

        if (! $this->tableExists($connectionName, $table)) {
            return $this->result('health_cache', 'skipped:missing-table');
        }

        $keys = $this->healthCacheKeys();
        $cutoff = min(now()->timestamp, now()->subDays($this->retentionDays('health_cache_days'))->timestamp);
        $query = $this->connection($connectionName)->table($table)
            ->whereIn('key', $keys)
            ->where('expiration', '<', $cutoff);
        $matched = (clone $query)->count();
        $deleted = $dryRun ? 0 : (clone $query)->delete();

        return $this->result('health_cache', 'checked', $matched, $deleted);
    }

    private function retentionDays(string $key): int
    {
        return max(1, (int) config("privacy.technical_retention.{$key}", 30));
    }

    private function connection(?string $connectionName): ConnectionInterface
    {
        return DB::connection($connectionName);
    }

    private function tableExists(?string $connectionName, string $table): bool
    {
        return $this->connection($connectionName)->getSchemaBuilder()->hasTable($table);
    }

    /**
     * @return array{category: string, status: string, matched: int, deleted: int}
     */
    private function result(string $category, string $status, int $matched = 0, int $deleted = 0): array
    {
        return [
            'category' => $category,
            'status' => $status,
            'matched' => $matched,
            'deleted' => $deleted,
        ];
    }

    private function safeLogPath(): string
    {
        $configuredPath = (string) config('privacy.technical_retention.log_path', storage_path('logs'));
        $storageLogs = realpath(storage_path('logs'));

        if ($storageLogs === false) {
            throw new RuntimeException('storage/logs does not exist.');
        }

        if (! is_dir($configuredPath)) {
            return $configuredPath;
        }

        $realPath = realpath($configuredPath);

        if ($realPath === false || ! str_starts_with($realPath, $storageLogs)) {
            throw new RuntimeException('Configured log retention path must stay inside storage/logs.');
        }

        return $realPath;
    }

    private function isPurgeableDailyLog(SplFileInfo $file, string $logPath, Carbon $cutoff): bool
    {
        if ($file->isLink() || ! $file->isFile()) {
            return false;
        }

        $realFilePath = realpath($file->getPathname());
        $realLogPath = realpath($logPath);

        if ($realFilePath === false || $realLogPath === false || ! str_starts_with($realFilePath, $realLogPath)) {
            return false;
        }

        if (! preg_match('/^[A-Za-z0-9_.-]+\.log(?:\.\d+)?$|^[A-Za-z0-9_.-]+-\d{4}-\d{2}-\d{2}\.log$/', $file->getFilename())) {
            return false;
        }

        $date = $this->dailyLogDateFromFilename($file->getFilename());

        if ($date instanceof Carbon) {
            return $date->endOfDay()->lessThan($cutoff);
        }

        if (preg_match('/^[A-Za-z0-9_.-]+\.log$/', $file->getFilename())) {
            return false;
        }

        return Carbon::createFromTimestamp($file->getMTime())->lessThan($cutoff);
    }

    private function dailyLogDateFromFilename(string $filename): ?Carbon
    {
        if (! preg_match('/^[A-Za-z0-9_.-]+-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private function healthCacheKeys(): array
    {
        $keys = array_filter([
            config('health.queue_heartbeat_key'),
            config('health.scheduler_heartbeat_key'),
        ], static fn (mixed $key): bool => is_string($key) && $key !== '');
        $prefix = (string) config('cache.prefix', '');

        return collect($keys)
            ->flatMap(fn (string $key): array => $prefix !== '' ? [$key, $prefix.$key] : [$key])
            ->unique()
            ->values()
            ->all();
    }
}
