<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Console\Commands;

use App\Support\OperationalHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class VisitorPortalHealthCommand extends Command
{
    protected $signature = 'visitorportal:health {target=app : app|queue|scheduler}';

    protected $description = 'Run operational health checks for VisitorPortal services.';

    public function handle(): int
    {
        $target = strtolower((string) $this->argument('target'));

        try {
            match ($target) {
                'app' => $this->checkApp(),
                'queue' => $this->checkQueue(),
                'scheduler' => $this->checkScheduler(),
                default => throw new RuntimeException('target must be app, queue, or scheduler'),
            };

            $this->info('OK '.$target);

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('FAIL '.$target.': '.$this->safeReason($exception));

            return Command::FAILURE;
        }
    }

    private function checkApp(): void
    {
        $this->checkDatabase();
        $this->checkWritableDirectories();
    }

    private function checkQueue(): void
    {
        $this->checkDatabase();
        $this->checkTable('jobs');
        $this->checkTable('failed_jobs');

        if (! app(OperationalHeartbeat::class)->queueIsFresh()) {
            throw new RuntimeException('heartbeat is stale');
        }
    }

    private function checkScheduler(): void
    {
        $this->checkDatabase();
        $this->checkHealthCacheStore();

        if (! app(OperationalHeartbeat::class)->schedulerIsFresh()) {
            throw new RuntimeException('heartbeat is stale');
        }
    }

    private function checkDatabase(): void
    {
        DB::connection()->getPdo();
        DB::select('select 1');
    }

    private function checkTable(string $table): void
    {
        DB::table($table)->limit(1)->exists();
    }

    private function checkHealthCacheStore(): void
    {
        $key = 'health:probe:'.Str::uuid()->toString();
        $cache = Cache::store(config('health.cache_store'));

        $cache->put($key, 'ok', now()->addMinute());

        try {
            if ($cache->get($key) !== 'ok') {
                throw new RuntimeException('health cache store is unavailable');
            }
        } finally {
            $cache->forget($key);
        }
    }

    private function checkWritableDirectories(): void
    {
        $directories = [
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($directories as $label => $path) {
            if (! is_dir($path)) {
                throw new RuntimeException($label.' is missing');
            }

            if (! is_writable($path)) {
                throw new RuntimeException($label.' is not writable');
            }
        }
    }

    private function safeReason(Throwable $exception): string
    {
        $message = trim((string) preg_replace('/\s+/', ' ', $exception->getMessage()));

        if ($message === '') {
            return 'health check failed';
        }

        $secretValues = [
            env('APP_KEY'),
            env('DB_PASSWORD'),
            env('DB_ROOT_PASSWORD'),
            env('OIDC_CLIENT_SECRET'),
            env('MAIL_PASSWORD'),
            env('AWS_SECRET_ACCESS_KEY'),
            config('app.key'),
            config('database.connections.mysql.password'),
            config('database.connections.mariadb.password'),
            config('database.connections.pgsql.password'),
            config('database.connections.sqlsrv.password'),
            config('mail.mailers.smtp.password'),
            config('sso.oidc.client_secret'),
        ];

        foreach ($secretValues as $secretValue) {
            $value = (string) $secretValue;

            if ($value !== '' && strtolower($value) !== 'null') {
                $message = str_replace($value, '[redacted]', $message);
            }
        }

        return Str::limit($message, 160, '');
    }
}
