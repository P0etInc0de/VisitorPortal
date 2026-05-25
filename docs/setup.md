# Setup

VisitorPortal has two supported setup paths:

- Demo setup: start the released Docker image with the bundled scripts.
- Development setup: mount the source tree with `docker-compose.yml` and build locally.

## Demo Setup

Use the demo setup when you want to try VisitorPortal without installing PHP, Composer, npm or Node.js on your machine.

Requirements:

- Docker Desktop or Docker Engine is installed and running.
- Docker Compose v2 is available as `docker compose`.

Windows:

```bat
start.bat
```

macOS/Linux:

```bash
sh start.sh
```

The start script:

- creates `.env.demo` from `.env.demo.example` when needed,
- checks Docker and Docker Compose v2,
- pulls the VisitorPortal image,
- starts the app, queue, scheduler, database, Mailhog and Gotenberg containers,
- runs setup inside the app container,
- loads demo data.

After startup:

- App: [http://localhost:8080](http://localhost:8080)
- Mailhog: [http://localhost:8025](http://localhost:8025)

If the default ports are already used, set different host ports in `.env.demo`:

```env
APP_PORT=8081
MAILHOG_PORT=8026
```

## Demo Accounts

All demo accounts use `ChangeMe-42!`.

| Role | Email |
| --- | --- |
| Admin | `admin@example.org` |
| Reception | `reception@example.org` |
| Employee | `employee@example.org` |
| Manager | `manager@example.org` |
| Welcome monitor | `welcome@example.org` |
| Security/reception | `security@example.org` |

Demo users, visitors and visits use reserved `example.org`, `example.com` and `example.net` domains. Demo seeders are blocked in `APP_ENV=production`. Do not use demo credentials in production.

## Stop, Reset And Update

Stop the demo while keeping local volumes:

```bash
sh stop.sh
```

Reset the demo database and storage volumes:

```bash
sh reset-demo.sh
```

Pull and restart the configured demo image:

```bash
sh update.sh
```

Windows users can use the matching `.bat` scripts.

## Release Version Pinning

Official demo ZIPs contain `.env.demo.example` with the published release tag:

```env
VISITORPORTAL_VERSION=v1.2.0
```

This keeps demos reproducible. Source checkouts contain `RELEASE_VERSION_PLACEHOLDER` until the release workflow replaces it. If you build a demo package manually, replace the placeholder before publishing it.

See [Release Artifacts](release-artifacts.md) for which ZIP to download.

## Development Setup

The development stack uses `docker-compose.yml`. Its web service is named `web`.

```bash
cp backend/.env.example backend/.env
docker compose run --rm web composer install
docker compose up -d --build
docker compose exec web php artisan key:generate
docker compose exec web php artisan migrate:fresh --seed
```

Development URLs:

- App: [http://localhost:8080](http://localhost:8080)
- Vite dev server: [http://localhost:5173](http://localhost:5173)
- Mailhog: [http://localhost:8025](http://localhost:8025)

Development services:

- `web`: Apache, PHP 8.4 and Laravel
- `node`: Node.js 24 frontend tooling
- `db`: MariaDB 11.4
- `queue`: Laravel queue worker
- `scheduler`: Laravel scheduler worker
- `mailhog`: local mail testing
- `gotenberg`: PDF rendering

## Useful Commands

Demo stack status:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml ps
```

Demo app logs:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs --tail=120 app
```

Development app logs:

```bash
docker compose logs --tail=120 web
```

Run tests in the development container:

```bash
docker compose exec web php artisan test
```

Run Pint in the development container:

```bash
docker compose exec web ./vendor/bin/pint --test
```

Build frontend assets:

```bash
docker compose run --rm node npm run build
```

## First-Run Troubleshooting

- Docker is not running: start Docker Desktop or the Docker daemon and run the script again.
- `Docker Compose v2 is required.`: update Docker Desktop or install the Docker Compose plugin.
- Port `8080` or `8025` is already used: change `APP_PORT` or `MAILHOG_PORT` in `.env.demo`, then restart.
- Image pull failed: check network access to GitHub Container Registry and the configured `VISITORPORTAL_VERSION`. The script continues if the image is already available locally.
- App is unhealthy after startup: wait a little longer on first boot, then inspect `app` and `db` logs. The demo app healthcheck has a longer startup grace period because migrations and seeding can take time.
- Database is still starting: inspect `docker compose --env-file .env.demo -f docker-compose.demo.yml logs --tail=120 db` and retry after the DB is healthy.
- You want a clean demo: run `reset-demo.sh` or `reset-demo.bat`. This deletes local demo volumes.
- You want a newer release: download the new demo ZIP or set `VISITORPORTAL_VERSION` in `.env.demo`, then run the update script.

## Running Artisan Without Docker

Docker is the recommended development environment. If you run Artisan directly on the host, the local PHP CLI must provide the required extensions, including `pdo_mysql`, `dom`, `mbstring`, `xml`, `xmlwriter`, `intl`, `zip`, `fileinfo` and `gmp`.

Ubuntu/WSL example:

```bash
sudo apt update
sudo apt install php8.4-mysql php8.4-xml php8.4-mbstring php8.4-intl php8.4-zip php8.4-gmp
```

Check loaded extensions:

```bash
php -m | grep -E 'PDO|pdo_mysql|dom|mbstring|xml|xmlwriter|intl|zip|gmp'
```
