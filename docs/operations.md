# Monitoring and Operations

VisitorPortal does not include an active monitoring system. Operators are responsible for integrating the application into their monitoring stack.

## Recommended checks

- HTTP check: GET `/up`
- Container state: app, queue, scheduler, db (MariaDB), gotenberg
- Docker health status
- Queue backlog
- Failed jobs
- Scheduler heartbeat
- Disk usage for storage and database volumes
- SMTP delivery
- Database backups

## Health endpoints and commands

- `GET /up`
- `php artisan visitorportal:health app`
- `php artisan visitorportal:health queue`
- `php artisan visitorportal:health scheduler`

For the default development compose file, use `web` instead of `app`:

```bash
docker compose exec web php artisan visitorportal:health app
```

## Docker

Use a current Docker Engine or Docker Desktop version with Docker Compose v2.

Use restart policies:

```yaml
restart: unless-stopped
```

Docker Compose does not automatically restart a container only because it is unhealthy. Health status must be observed by an operator or monitoring system.

## Queue

The queue worker is required for notifications and background jobs.

Recommended command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --max-jobs=1000 --memory=128
```

Ensure `DB_QUEUE_RETRY_AFTER` is greater than the worker timeout.

## Privacy Retention

VisitorPortal purges business visit data with `php artisan visits:purge-expired` according to the privacy retention settings. It also schedules `php artisan privacy:purge-technical-data` when `PRIVACY_TECHNICAL_RETENTION_ENABLED=true` to remove old technical data outside the visit/visitor retention flow.

`privacy:purge-technical-data` covers database sessions, `failed_jobs`, `job_batches`, expired health heartbeat cache rows when they are safely identifiable, and old daily log files below `storage/logs`. It supports `--dry-run` and only prints aggregate counts, never session payloads, job payloads, log contents, secrets or personal data.

Use these commands for manual checks:

```bash
php artisan privacy:purge-technical-data --dry-run
php artisan privacy:purge-technical-data
```

This command is application retention only. It does not replace backup retention, Docker volume retention, database backup retention, reverse-proxy log retention, Docker daemon or host log retention, or retention in external SIEM, monitoring and log aggregation systems.

## Troubleshooting

For a stricter storage permission check, run the app health command as the web server user when the container permits it.

```bash
docker compose ps
docker compose logs -f queue
docker compose logs -f scheduler
docker compose exec app php artisan visitorportal:health app
docker compose exec --user www-data app php artisan visitorportal:health app
docker compose exec queue php artisan visitorportal:health queue
docker compose exec scheduler php artisan visitorportal:health scheduler
docker compose exec app php artisan queue:failed
```
