# Production Deployment

This guide describes a production bootstrap without demo users or demo seed data. Demo seeders are blocked in `APP_ENV=production`.

## Requirements

- PHP 8.4 with `pdo_mysql`, `mbstring`, `intl`, `zip`, `fileinfo`, `dom`, `xml`, `xmlwriter` and `gmp` for LAMP deployments.
- MariaDB 11.4 as the default database.
- Composer for LAMP deployments.
- Node.js 24 only when frontend assets are built on the target system.
- SMTP server for production e-mail.
- Gotenberg 8.32 for badge PDF rendering.
- HTTPS in front of the application.
- Docker Engine or Docker Desktop with Docker Compose v2 for container deployments.

## Choose The Right Artifact

- For a quick demo, use `VisitorPortal-demo-vX.Y.Z.zip`.
- For production Docker deployments, use a fixed container image tag such as `ghcr.io/p0etinc0de/besucherportal:vX.Y.Z` with `docker-compose.prod.yml`.
- For source-based deployments, clone the repository or use `besucherportal-vX.Y.Z.zip`.

See [Release Artifacts](release-artifacts.md) for details.

## Production Environment

Create a real production `.env` from `backend/.env.production.example`.

For Docker production, place `.env` in the repository root next to `docker-compose.prod.yml`.

For LAMP deployments, place it at `backend/.env`.

Minimum production checks:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://visitorportal.example.com
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
AUTO_MIGRATE=false
AUTO_SEED=false
FORCE_SEED=false
```

Generate `APP_KEY` once in a trusted environment and keep it stable. Changing it invalidates encrypted application data and sessions.

## Docker Production

`docker-compose.prod.yml` is the production container stack.

Important properties:

- The web service is named `app`.
- The database and Gotenberg are not published to the host by default.
- Mailhog is not included; configure real SMTP through `MAIL_*`.
- `VISITORPORTAL_IMAGE` is required and should point to a fixed release tag.
- `AUTO_MIGRATE=false` and `AUTO_SEED=false` are safe defaults.

Start the stack:

```bash
docker compose -f docker-compose.prod.yml up -d
```

Run the production bootstrap:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:install
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:create-admin
```

Alternatively, create the initial admin during install:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:install --create-admin
```

The bootstrap does not create demo users and does not use a default password. Admin passwords are entered interactively.

## LAMP Deployment

1. Copy the project to the server.
2. Point the web server document root to `backend/public`.
3. Install dependencies and build assets in `backend/`.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan storage:link
php artisan visitorportal:install
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

4. Ensure the web server user can write to `backend/storage` and `backend/bootstrap/cache`.
5. Run a queue worker continuously.
6. Run the scheduler every minute through cron or systemd.

## Roles And Permissions

Synchronize roles and permissions after installation and after updates that change authorization:

```bash
php artisan visitorportal:sync-permissions
```

The command is idempotent. See [Roles and Permissions](roles.md).

## MFA And Admin Accounts

Keep MFA enabled for production. Default policy requires local App-MFA for `admin` and `super_admin` when configured login methods require it.

```env
MFA_ENABLED=true
MFA_OPTIONAL_FOR_USERS=true
APP_MFA_REQUIRED_ROLES=admin,super_admin
APP_MFA_REQUIRED_FOR_AUTH_METHODS=local
APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS=local,sso
APP_MFA_SESSION_TTL_MINUTES=720
```

Use personal admin accounts instead of shared admin accounts. Store recovery codes immediately after setup.

If a user loses access to MFA, a trusted operator with shell access can reset local App-MFA:

```bash
php artisan visitorportal:disable-mfa user@example.org
```

The command asks for confirmation unless `--force` is used deliberately.

## Queue Worker

Production requires a running queue worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --max-jobs=1000 --memory=128
```

Set `DB_QUEUE_RETRY_AFTER=180` so it is greater than the worker timeout. See [Queue](queue.md).

## Scheduler

Production requires the scheduler every minute.

LAMP cron example:

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Docker production uses the `scheduler` service with `php artisan schedule:work`. See [Scheduler](scheduler.md).

## Reverse Proxy And TLS

- Set `APP_URL=https://...`.
- Set `SESSION_SECURE_COOKIE=true`.
- Set `SESSION_ENCRYPT=true`.
- Set `TRUSTED_PROXIES` to the proxy IP or trusted internal proxy network.
- Use `TRUSTED_PROXIES=*` only in controlled container/proxy networks.
- Forward `X-Forwarded-Proto`, `X-Forwarded-Host`, `X-Forwarded-Port` and `X-Forwarded-For` correctly.

HSTS and secure-cookie behavior depend on Laravel detecting HTTPS correctly. Test the full browser-to-application HTTPS path after configuring the reverse proxy.

## Content Security Policy

The default CSP is compatible with Livewire, Alpine.js and Filament. It currently allows `unsafe-inline` and `unsafe-eval` for framework compatibility and is not a strict locked-down CSP profile.

Current production shape:

```text
default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob:; font-src 'self' data:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; connect-src 'self'
```

Test stricter CSP changes in report-only mode first.

## Mail

Configure real SMTP in production. Test password reset, host notifications and guest-facing mails in staging before processing real visitor data.

If `PRIVACY_NOTICE_URL` is set, guest-facing mail templates can include the operator-owned privacy notice link. Internal employee forms do not automatically display it.

## Gotenberg

`GOTENBERG_URL` must be reachable by the app for badge PDFs. Docker defaults to `http://gotenberg:3000` and uses Gotenberg 8.32.

## Storage And Uploads

Run the storage link command for LAMP deployments:

```bash
php artisan storage:link
```

Backups must include the database and `backend/storage`.

Branding, logo and welcome-monitor images are public display assets. Depending on the storage disk and `storage:link`, uploaded monitor backgrounds can be publicly reachable under `/storage/...`. Do not upload confidential, personal or sensitive information as display images.

JPEG, PNG and WebP uploads are validated, dimension-checked and metadata-stripped before storage. SVG uploads are not accepted through the monitor upload forms.

## Retention

Visit retention:

```bash
php artisan visits:purge-expired --dry-run
php artisan visits:purge-expired
```

Technical retention:

```bash
php artisan privacy:purge-technical-data --dry-run
php artisan privacy:purge-technical-data
```

Application retention does not replace backup retention, Docker volume retention, database backup retention, reverse-proxy log retention, Docker daemon log retention, host log retention or SIEM retention. Configure those separately.

## Updates

Recommended update flow:

1. Create and verify a backup.
2. Enable maintenance mode.
3. Update code or image.
4. Run migrations.
5. Synchronize permissions.
6. Rebuild caches.
7. Restart queue workers.
8. Disable maintenance mode.

```bash
php artisan down
php artisan migrate --force
php artisan visitorportal:sync-permissions
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

Docker production uses the `app` service for Artisan commands:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

Before a real go-live, complete the [Go-Live Checklist](go-live-checklist.md).
