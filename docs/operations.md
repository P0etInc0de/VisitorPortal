# Monitoring and Operations

VisitorPortal does not include an active monitoring system. Operators are responsible for integrating the application into their monitoring stack.

## Recommended checks

- HTTP check: GET `/up`
- Container state: app, queue, scheduler, db, gotenberg
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

## Docker

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

## Troubleshooting

```bash
docker compose ps
docker compose logs -f queue
docker compose logs -f scheduler
docker compose exec app php artisan visitorportal:health app
docker compose exec queue php artisan visitorportal:health queue
docker compose exec scheduler php artisan visitorportal:health scheduler
docker compose exec app php artisan queue:failed
```
