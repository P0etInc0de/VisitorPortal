# Monitoring and Operations

VisitorPortal does not include an active monitoring system. Operators are responsible for integrating the application into their monitoring stack.

## Recommended checks

- HTTP check: GET `/up`
- Container state: `app` in demo/production or `web` in development, plus `queue`, `scheduler`, `db` (MariaDB), and `gotenberg`
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

The web service name depends on the Compose file:

- Demo and production use `app` (`docker-compose.demo.yml`, `docker-compose.prod.yml`).
- Development uses `web` (`docker-compose.yml`).

Development example:

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

`privacy:purge-technical-data` covers database sessions, `failed_jobs`, `job_batches`, expired health heartbeat cache rows when they are safely identifiable, and old daily log files below `storage/logs`. It does not delete `storage/logs/laravel.log` when `LOG_CHANNEL=single` is used. It supports `--dry-run` and only prints aggregate counts, never session payloads, job payloads, log contents, secrets or personal data.

Use these commands for manual checks:

```bash
php artisan privacy:purge-technical-data --dry-run
php artisan privacy:purge-technical-data
```

This command is application retention only. It does not replace backup retention, Docker volume retention, database backup retention, reverse-proxy log retention, Docker daemon or host log retention, or retention in external SIEM, monitoring and log aggregation systems. Operators must configure those retention policies separately.

## Troubleshooting

Use the same Compose file and environment file options that were used to start the stack. The web service is named `app` for demo/production and `web` for development.

Demo:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml ps
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f app
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f queue
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f scheduler
docker compose --env-file .env.demo -f docker-compose.demo.yml exec app php artisan visitorportal:health app
docker compose --env-file .env.demo -f docker-compose.demo.yml exec --user www-data app php artisan visitorportal:health app
docker compose --env-file .env.demo -f docker-compose.demo.yml exec queue php artisan visitorportal:health queue
docker compose --env-file .env.demo -f docker-compose.demo.yml exec scheduler php artisan visitorportal:health scheduler
docker compose --env-file .env.demo -f docker-compose.demo.yml exec app php artisan queue:failed
```

Production:

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f queue
docker compose -f docker-compose.prod.yml logs -f scheduler
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:health app
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan visitorportal:health app
docker compose -f docker-compose.prod.yml exec queue php artisan visitorportal:health queue
docker compose -f docker-compose.prod.yml exec scheduler php artisan visitorportal:health scheduler
docker compose -f docker-compose.prod.yml exec app php artisan queue:failed
```

Development:

```bash
docker compose ps
docker compose logs -f web
docker compose logs -f queue
docker compose logs -f scheduler
docker compose exec web php artisan visitorportal:health app
docker compose exec queue php artisan visitorportal:health queue
docker compose exec scheduler php artisan visitorportal:health scheduler
docker compose exec web php artisan queue:failed
```

Use `exec --user www-data app` for a stricter storage permission check when the container permits it.
