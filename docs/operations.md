# Monitoring and Operations

VisitorPortal does not include a full monitoring or alerting system. Operators must connect it to their own monitoring stack.

## Services

Service names depend on the Compose file:

- Development (`docker-compose.yml`): web service `web`.
- Demo (`docker-compose.demo.yml`): web service `app`.
- Production (`docker-compose.prod.yml`): web service `app`.

Shared service names:

- `queue`: Laravel queue worker.
- `scheduler`: Laravel scheduler worker.
- `db`: MariaDB.
- `gotenberg`: PDF renderer.
- `mailhog`: demo/development mail testing only.

## Recommended Checks

- HTTP check: `GET /up`.
- Docker container state and Docker health status.
- App health: `php artisan visitorportal:health app`.
- Queue health: `php artisan visitorportal:health queue`.
- Scheduler health: `php artisan visitorportal:health scheduler`.
- Queue backlog and failed jobs.
- Scheduler heartbeat freshness.
- Disk usage for database and storage volumes.
- SMTP delivery.
- Database backups and restore tests.
- Retention dry-runs.

Docker Compose does not restart a container only because it is unhealthy. Observe health status through monitoring and alerting.

## Health Commands

Development:

```bash
docker compose exec web php artisan visitorportal:health app
docker compose exec queue php artisan visitorportal:health queue
docker compose exec scheduler php artisan visitorportal:health scheduler
```

Demo:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml exec app php artisan visitorportal:health app
docker compose --env-file .env.demo -f docker-compose.demo.yml exec queue php artisan visitorportal:health queue
docker compose --env-file .env.demo -f docker-compose.demo.yml exec scheduler php artisan visitorportal:health scheduler
```

Production:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:health app
docker compose -f docker-compose.prod.yml exec queue php artisan visitorportal:health queue
docker compose -f docker-compose.prod.yml exec scheduler php artisan visitorportal:health scheduler
```

For stricter storage permission checks in demo or production, run app health as the web server user when the container permits it:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml exec --user www-data app php artisan visitorportal:health app
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan visitorportal:health app
```

## Logs

Development:

```bash
docker compose logs -f web
docker compose logs -f queue
docker compose logs -f scheduler
```

Demo:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f app
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f queue
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f scheduler
```

Production:

```bash
docker compose -f docker-compose.prod.yml logs -f app
docker compose -f docker-compose.prod.yml logs -f queue
docker compose -f docker-compose.prod.yml logs -f scheduler
```

## Queue Operations

The queue worker is required for notifications and background jobs.

Check failed jobs:

```bash
php artisan queue:failed
```

Restart workers after deployments:

```bash
php artisan queue:restart
```

Recommended worker command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --max-jobs=1000 --memory=128
```

`DB_QUEUE_RETRY_AFTER` must be greater than the worker timeout. The Docker default is `180`.

## Scheduler Operations

The scheduler is required for reminders, monitor auto generation, recurring visits, completion tasks, retention jobs and health heartbeat updates.

List tasks:

```bash
php artisan schedule:list
```

Run once:

```bash
php artisan schedule:run
```

Docker uses the `scheduler` service with `php artisan schedule:work`.

## Retention Checks

Visit retention dry-run:

```bash
php artisan visits:purge-expired --dry-run
```

Technical retention dry-run:

```bash
php artisan privacy:purge-technical-data --dry-run
```

Retention output contains aggregate counts only. It must not be treated as a replacement for backup, database, Docker, host, reverse-proxy or SIEM retention policies.

## Backup Checks

Backups must cover:

- MariaDB data.
- `backend/storage` or the equivalent Docker `app_storage` volume.
- Production `.env` secrets stored securely outside the application checkout.
- Uploaded branding and welcome-monitor assets.

Test restores regularly. A backup that has never been restored is not an operationally verified backup.

## Troubleshooting

Container status:

```bash
docker compose ps
docker compose --env-file .env.demo -f docker-compose.demo.yml ps
docker compose -f docker-compose.prod.yml ps
```

App does not become healthy:

- Check `app` or `web` logs.
- Check database health and credentials.
- Confirm `APP_KEY` is set.
- Confirm writable `storage` and `bootstrap/cache`.
- On first demo start, allow time for migrations and seed data.

Queue health is stale:

- Confirm the `queue` service is running.
- Inspect failed jobs.
- Check SMTP connectivity if mail jobs fail.

Scheduler health is stale:

- Confirm the `scheduler` service or cron entry is running.
- Check `schedule:list` and scheduler logs.

Port conflicts in the demo:

- Change `APP_PORT` or `MAILHOG_PORT` in `.env.demo`.
- Restart the demo.

Reset the local demo:

```bash
sh reset-demo.sh
```
