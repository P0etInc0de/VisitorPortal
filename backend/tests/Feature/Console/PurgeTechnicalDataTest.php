<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurgeTechnicalDataTest extends TestCase
{
    use RefreshDatabase;

    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 5, 21, 12, 0, 0, config('app.timezone')));

        $this->logPath = storage_path('logs/testing-technical-retention-'.Str::uuid());
        File::ensureDirectoryExists($this->logPath);

        config()->set('privacy.technical_retention.enabled', true);
        config()->set('privacy.technical_retention.session_days', 30);
        config()->set('privacy.technical_retention.failed_job_days', 30);
        config()->set('privacy.technical_retention.job_batch_days', 30);
        config()->set('privacy.technical_retention.log_days', 30);
        config()->set('privacy.technical_retention.health_cache_days', 7);
        config()->set('privacy.technical_retention.log_path', $this->logPath);
        config()->set('session.driver', 'database');
    }

    protected function tearDown(): void
    {
        if (isset($this->logPath) && str_starts_with($this->logPath, storage_path('logs'))) {
            File::deleteDirectory($this->logPath);
        }

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_old_database_sessions_are_deleted_and_young_sessions_remain(): void
    {
        $this->insertSession('old-session', now()->subDays(31)->timestamp, 'old-session-payload@example.test');
        $this->insertSession('young-session', now()->subDay()->timestamp, 'young-session-payload@example.test');

        $exitCode = Artisan::call('privacy:purge-technical-data');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('sessions: status=checked, matched=1, deleted=1', $output);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'young-session']);
    }

    public function test_old_failed_jobs_are_deleted_and_young_failed_jobs_remain(): void
    {
        $oldUuid = (string) Str::uuid();
        $youngUuid = (string) Str::uuid();

        $this->insertFailedJob($oldUuid, now()->subDays(31), 'old failed job payload secret@example.test');
        $this->insertFailedJob($youngUuid, now()->subDay(), 'young failed job payload secret@example.test');

        $exitCode = Artisan::call('privacy:purge-technical-data');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('failed_jobs: status=checked, matched=1, deleted=1', $output);
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $oldUuid]);
        $this->assertDatabaseHas('failed_jobs', ['uuid' => $youngUuid]);
    }

    public function test_old_job_batches_are_deleted_and_young_job_batches_remain(): void
    {
        $oldBatchId = (string) Str::uuid();
        $cancelledBatchId = (string) Str::uuid();
        $youngBatchId = (string) Str::uuid();

        $this->insertJobBatch($oldBatchId, now()->subDays(40)->timestamp, now()->subDays(31)->timestamp, null);
        $this->insertJobBatch($cancelledBatchId, now()->subDays(40)->timestamp, null, now()->subDays(31)->timestamp);
        $this->insertJobBatch($youngBatchId, now()->subDay()->timestamp, now()->subDay()->timestamp, null);

        $exitCode = Artisan::call('privacy:purge-technical-data');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('job_batches: status=checked, matched=2, deleted=2', $output);
        $this->assertDatabaseMissing('job_batches', ['id' => $oldBatchId]);
        $this->assertDatabaseMissing('job_batches', ['id' => $cancelledBatchId]);
        $this->assertDatabaseHas('job_batches', ['id' => $youngBatchId]);
    }

    public function test_missing_job_batches_table_is_skipped_without_error(): void
    {
        config()->set('queue.batching.table', 'missing_job_batches');

        $exitCode = Artisan::call('privacy:purge-technical-data');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('job_batches: status=skipped:missing-table, matched=0, deleted=0', $output);
    }

    public function test_dry_run_deletes_nothing_and_outputs_only_aggregate_counts(): void
    {
        $oldLog = $this->writeDailyLog('laravel-2026-04-01.log', 'Sensitive Log Person secret@example.test');
        $this->insertSession('old-session', now()->subDays(31)->timestamp, 'session payload secret@example.test');
        $this->insertFailedJob((string) Str::uuid(), now()->subDays(31), 'failed job payload secret@example.test');

        $exitCode = Artisan::call('privacy:purge-technical-data', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Technical privacy purge dry-run.', $output);
        $this->assertStringContainsString('sessions: status=checked, matched=1, deleted=0', $output);
        $this->assertStringContainsString('failed_jobs: status=checked, matched=1, deleted=0', $output);
        $this->assertStringContainsString('daily_logs: status=checked, matched=1, deleted=0', $output);
        $this->assertDatabaseHas('sessions', ['id' => 'old-session']);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->assertFileExists($oldLog);
        $this->assertStringNotContainsString('session payload secret@example.test', $output);
        $this->assertStringNotContainsString('failed job payload secret@example.test', $output);
        $this->assertStringNotContainsString('Sensitive Log Person', $output);
        $this->assertStringNotContainsString('secret@example.test', $output);
    }

    public function test_old_daily_log_files_are_deleted_and_current_logs_remain(): void
    {
        $oldDailyLog = $this->writeDailyLog('laravel-2026-04-01.log', 'old daily log content', now()->timestamp);
        $currentDailyLog = $this->writeDailyLog('laravel-2026-05-21.log', 'current daily log content', now()->subDays(90)->timestamp);
        $singleLog = $this->writeLog('laravel.log', 'single log content', now()->subDays(90)->timestamp);

        $exitCode = Artisan::call('privacy:purge-technical-data');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('daily_logs: status=checked, matched=1, deleted=1', $output);
        $this->assertFileDoesNotExist($oldDailyLog);
        $this->assertFileExists($currentDailyLog);
        $this->assertFileExists($singleLog);
    }

    public function test_rotated_log_without_date_falls_back_to_mtime(): void
    {
        $oldRotatedLog = $this->writeLog('laravel.log.1', 'old rotated log content', now()->subDays(90)->timestamp);
        $currentRotatedLog = $this->writeLog('laravel.log.2', 'current rotated log content', now()->timestamp);

        $exitCode = Artisan::call('privacy:purge-technical-data');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('daily_logs: status=checked, matched=1, deleted=1', $output);
        $this->assertFileDoesNotExist($oldRotatedLog);
        $this->assertFileExists($currentRotatedLog);
    }

    public function test_command_is_idempotent(): void
    {
        $oldUuid = (string) Str::uuid();

        $this->insertSession('old-session', now()->subDays(31)->timestamp, 'old session payload');
        $this->insertFailedJob($oldUuid, now()->subDays(31), 'old failed job payload');
        $this->writeDailyLog('laravel-2026-04-01.log', 'old daily log content');

        $firstExitCode = Artisan::call('privacy:purge-technical-data');
        $secondExitCode = Artisan::call('privacy:purge-technical-data');
        $secondOutput = Artisan::output();

        $this->assertSame(Command::SUCCESS, $firstExitCode);
        $this->assertSame(Command::SUCCESS, $secondExitCode);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertDatabaseMissing('failed_jobs', ['uuid' => $oldUuid]);
        $this->assertStringContainsString('sessions: status=checked, matched=0, deleted=0', $secondOutput);
        $this->assertStringContainsString('failed_jobs: status=checked, matched=0, deleted=0', $secondOutput);
        $this->assertStringContainsString('daily_logs: status=checked, matched=0, deleted=0', $secondOutput);
    }

    private function insertSession(string $id, int $lastActivity, string $payload): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
            'payload' => $payload,
            'last_activity' => $lastActivity,
        ]);
    }

    private function insertFailedJob(string $uuid, Carbon $failedAt, string $payload): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => $payload,
            'exception' => 'Test exception with secret-like payload',
            'failed_at' => $failedAt,
        ]);
    }

    private function insertJobBatch(string $id, int $createdAt, ?int $finishedAt, ?int $cancelledAt): void
    {
        DB::table('job_batches')->insert([
            'id' => $id,
            'name' => 'Test Batch',
            'total_jobs' => 1,
            'pending_jobs' => 0,
            'failed_jobs' => 0,
            'failed_job_ids' => '[]',
            'options' => '{"secret":"batch-secret@example.test"}',
            'cancelled_at' => $cancelledAt,
            'created_at' => $createdAt,
            'finished_at' => $finishedAt,
        ]);
    }

    private function writeDailyLog(string $name, string $content, ?int $modifiedAt = null): string
    {
        return $this->writeLog($name, $content, $modifiedAt ?? now()->subDays(31)->timestamp);
    }

    private function writeLog(string $name, string $content, int $modifiedAt): string
    {
        $path = $this->logPath.DIRECTORY_SEPARATOR.$name;

        File::put($path, $content);
        touch($path, $modifiedAt);

        return $path;
    }
}
