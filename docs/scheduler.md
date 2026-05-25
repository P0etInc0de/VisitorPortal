# Scheduler

The scheduler runs time-based application tasks. Production deployments must run it continuously.

## What The Scheduler Does

Scheduled tasks are defined in `backend/routes/console.php`:

- `DailyVisitorReminder`: daily at `07:00`; sends host reminders for today's visits.
- `WelcomeMonitorAutoGeneration`: every minute; refreshes generated welcome-monitor slides when auto generation is enabled.
- `CompleteFinishedVisits`: every minute; completes visits whose scheduled window has ended when applicable.
- `RecurringVisitSeriesExpansion`: daily at `02:30`; expands recurring visit series.
- Scheduler heartbeat: every minute; updates operational health state.
- `visits:purge-expired`: daily at `03:15` when `PRIVACY_PURGE_ENABLED=true`.
- `privacy:purge-technical-data`: daily at `03:45` when `PRIVACY_TECHNICAL_RETENTION_ENABLED=true`.

## Required Runtime

Docker demo and production stacks run a dedicated `scheduler` service:

```bash
php artisan schedule:work
```

For LAMP deployments, run Laravel's scheduler once per minute through cron or systemd:

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Do not rely on web requests to trigger scheduled work.

## Commands

List scheduled tasks:

```bash
php artisan schedule:list
```

Run the scheduler once:

```bash
php artisan schedule:run
```

Check scheduler health:

```bash
php artisan visitorportal:health scheduler
```

Demo logs:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f scheduler
```

Production logs:

```bash
docker compose -f docker-compose.prod.yml logs -f scheduler
```

## Retention Jobs

Visit retention:

```bash
php artisan visits:purge-expired --dry-run
php artisan visits:purge-expired
```

`visits:purge-expired` removes expired visit data after `PRIVACY_VISIT_RETENTION_DAYS` unless a legal hold is active. It also removes related expired operational data such as old notifications, generated monitor slides and obsolete manual monitor visitor names where it is safe to do so.

Technical retention:

```bash
php artisan privacy:purge-technical-data --dry-run
php artisan privacy:purge-technical-data
```

`privacy:purge-technical-data` covers old database sessions, `failed_jobs`, `job_batches`, health heartbeat cache rows that are safely identifiable, and old daily log files below `storage/logs`. It does not delete backups, Docker volumes, reverse-proxy logs, Docker daemon logs, host logs, SIEM data or `storage/logs/laravel.log` when `LOG_CHANNEL=single` is used.

## Troubleshooting

- Scheduler health is stale: confirm the `scheduler` service or cron entry is running.
- Retention did not run: check `PRIVACY_PURGE_ENABLED` and `PRIVACY_TECHNICAL_RETENTION_ENABLED`, then inspect scheduler logs.
- Commands overlap: the retention commands use `withoutOverlapping`; check for long-running previous executions.
- Time appears wrong: verify container or server timezone and `APP_TIMEZONE`.

Reference: [Laravel 12 Task Scheduling](https://laravel.com/docs/12.x/scheduling).
