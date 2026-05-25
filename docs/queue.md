# Queue

The queue worker is required for host notifications, mail delivery and background work. Production deployments must run a queue worker continuously.

## Driver

VisitorPortal uses Laravel's database queue by default:

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
```

The queue tables are created by the normal migrations. Jobs are stored in `jobs`; failed jobs are stored in `failed_jobs`.

## Worker Command

Recommended worker command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --max-jobs=1000 --memory=128
```

`DB_QUEUE_RETRY_AFTER` must be greater than `--timeout`. The shipped Docker default is `180`, which is greater than the `120` second worker timeout.

Docker Compose uses the `queue` service for this command in demo and production.

## Operations

Check failed jobs:

```bash
php artisan queue:failed
```

Check queue health:

```bash
php artisan visitorportal:health queue
```

Demo logs:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs -f queue
```

Production logs:

```bash
docker compose -f docker-compose.prod.yml logs -f queue
```

Development logs:

```bash
docker compose logs -f queue
```

Restart workers after deployments so they load new code:

```bash
php artisan queue:restart
```

## Troubleshooting

- Notifications or e-mails are delayed: confirm the `queue` service is running and healthy.
- Jobs fail repeatedly: inspect `php artisan queue:failed`, application logs and SMTP settings.
- Jobs are retried too early: ensure `DB_QUEUE_RETRY_AFTER` is greater than the worker `--timeout`.
- Deployment completed but old behavior remains: restart the queue worker with `php artisan queue:restart` or restart the container.

Reference: [Laravel 12 Queues](https://laravel.com/docs/12.x/queues).
