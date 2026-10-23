# Production Deployment

This guide describes a production bootstrap without demo users or demo seed data. Demo seeders are blocked in `APP_ENV=production`.

## Requirements

- PHP 8.4.27 or newer within the 8.4 series with `pdo_mysql`, `mbstring`, `intl`, `zip`, `fileinfo`, `dom`, `xml`, `xmlwriter` and `gmp` for LAMP deployments.
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

For the changes and requirements specific to v0.9.0 -> v0.9.1, see the [v0.9.1 release notes](releases/v0.9.1.md). This update adds no application database migrations or changes to application role/permission definitions. A fully migrated v0.9.0 installation is expected to have no new migrations to apply.

### PHP And Dependency Security Updates

The Docker images and CI use PHP 8.4.27. This is the minimum supported patch release and includes the critical bailout state-corruption fixes [GHSA-w8hw-w9fx-j79w](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w) and [GHSA-6wmf-4rv5-p3rp](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).

The Composer requirement is `~8.4.27`, meaning **PHP `>=8.4.27,<8.5`**. PHP 8.5 is not accepted by this release. Official Docker images include the patched runtime, installed PHP dependencies and built frontend assets; Docker hosts do not need their own PHP or Node.js installation.

For source-based Docker deployments, rebuild the application images with the updated base image (`docker compose build --pull` for development; `docker build --pull --target app -f docker/app/Dockerfile -t <release-image> .` for production). Deploy the rebuilt image to the app, queue and scheduler services. For prebuilt deployments, publish and select a new release image containing these updates.

Composer's post-autoload hook runs `filament:upgrade` to refresh published Filament assets and clear stale framework caches. The production Docker build copies these generated assets from the vendor stage into the final image. When updating dependencies on LAMP, run `composer install --no-dev --optimize-autoloader` with scripts enabled before rebuilding the runtime caches.

For LAMP deployments, update both CLI and Apache/PHP-FPM to a supported PHP 8.4 patch release before installing the committed `composer.lock`. Restart the PHP/web services and queue workers so all processes use the patched runtime and dependencies. Verify with `php -v`, `composer check-platform-reqs` and `composer audit`; also verify the web server's PHP runtime if it uses a different installation from the CLI.

### Preserve The Existing Installation

Before rollout, test the new release on an isolated copy of the previous release's database and storage, retaining its `APP_KEY` and disabling real outbound mail in the test environment. Check existing users, roles, MFA, SSO if enabled, visits, check-in/check-out, badge PDFs and uploaded branding/monitor assets. A fresh-install test alone does not verify an upgrade.

For the production update:

- Record the current image tag and digest and retain that image for rollback. Back up the existing production configuration before changing it.
- Keep the database, application storage, custom branding and existing `APP_KEY`. A new key prevents decryption of existing encrypted data, including MFA secrets.
- Use the existing deployment directory, Compose project name, environment files, overrides and persistent volumes. Adapt the commands below if the installation uses a customized stack.
- Keep production `AUTO_MIGRATE=false`, `AUTO_SEED=false` and `FORCE_SEED=false`; run migrations deliberately.
- Use the production update procedure for an existing installation. Demo reset scripts, `docker compose down -v`, `migrate:fresh`, database reseeding and `key:generate` are not update steps. The initial-install commands earlier in this guide are for new installations.

### Docker Production Update

The following commands use the supplied `docker-compose.prod.yml` and service names `app`, `queue` and `scheduler`. Execute each step successfully before continuing. Apply the same additional Compose options used by the existing deployment to every command.

#### 1. Select And Pull The Release Image

After recording the old image reference and backing up the original configuration, change only `VISITORPORTAL_IMAGE` in the existing production `.env`. For v0.9.1:

```env
VISITORPORTAL_IMAGE=ghcr.io/p0etinc0de/besucherportal:v0.9.1
```

Pull the application image before entering maintenance mode; the running containers still use the previous image:

```bash
docker compose -f docker-compose.prod.yml pull app queue scheduler
```

Retain all other settings, especially `APP_KEY`, database credentials, SMTP and SSO configuration. Pull only these application services for this update; MariaDB and Gotenberg do not need to be replaced.

#### 2. Pause Application Writes And Back Up Data

```bash
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan down
docker compose -f docker-compose.prod.yml stop --timeout 180 queue scheduler
```

Allow in-flight requests/jobs to finish, then take a consistent backup of MariaDB and the complete `app_storage` volume using the installation's verified backup/restore process. Retain the original production configuration and recorded old image reference with the backup. Background services must stay stopped during the update.

The default maintenance driver stores its marker in the persistent application storage, so it remains active across app-container replacement. Preserve that volume.

#### 3. Replace The App Container

```bash
docker compose -f docker-compose.prod.yml up -d --no-deps --force-recreate app
docker compose -f docker-compose.prod.yml logs --tail=100 app
```

Wait for the entrypoint to finish and report `Starting: apache2-foreground` before running the following commands. Resolve startup errors before proceeding. The app remains in maintenance mode until step 5; HTTP health checks may reflect that state.

#### 4. Verify The Runtime And Rebuild Caches

```bash
docker compose -f docker-compose.prod.yml exec app php -v
docker compose -f docker-compose.prod.yml exec app composer check-platform-reqs --no-dev
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan config:cache
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan route:cache
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan view:cache
```

For v0.9.0 -> v0.9.1, verify PHP 8.4.27 and expect no new migrations on an already fully migrated database. Run `visitorportal:sync-permissions` when a release changes application authorization definitions or its upgrade instructions require it; this release introduces no such changes.

The image already contains the installed dependencies and published Filament assets. Rebuilding dependencies inside a running production container is not required.

#### 5. Resume The App And Replace Background Containers

```bash
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan up
docker compose -f docker-compose.prod.yml up -d --no-deps --force-recreate queue scheduler
```

All three services must use the same new image. Recreating the queue and scheduler containers replaces their long-running PHP processes as well.

#### 6. Verify The Updated Installation

```bash
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml exec --user www-data app php artisan visitorportal:health app
docker compose -f docker-compose.prod.yml exec queue php artisan visitorportal:health queue
docker compose -f docker-compose.prod.yml exec scheduler php artisan visitorportal:health scheduler
docker compose -f docker-compose.prod.yml logs --tail=100 app queue scheduler
```

Allow queue and scheduler heartbeats time to refresh. Verify the public `/up` endpoint, login/MFA, SSO where used, existing visits, a check-in/check-out, badge PDF rendering, uploaded assets and SMTP delivery. Review failed jobs and application logs. See [Monitoring and Operations](operations.md) for ongoing checks.

### Source/LAMP Update

1. Record the current application version and preserve the production configuration. Ensure both CLI and Apache/PHP-FPM use PHP `>=8.4.27,<8.5`, with the extensions listed under Requirements. Use Node.js 24 for the frontend build.
2. Enable maintenance mode on the current installation, stop the queue/scheduler services or scheduled invocations, allow in-flight work to finish, and back up the database and storage.
3. Deploy the new source while retaining `backend/.env`, its `APP_KEY`, storage and custom branding files. Run the following commands from `backend/` with the installation's appropriate file ownership:

```bash
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
composer check-platform-reqs --no-dev
composer audit --no-dev
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

4. Restart the PHP/web services to load the patched runtime and clear any old OPcache state. Disable maintenance mode with `php artisan up`, then start the queue and scheduler with the new code and runtime.
5. Perform the same health and functional checks as for Docker deployments.

Use the committed lockfiles: `composer install` and `npm ci` install the release's dependency versions. Keep Composer scripts enabled so `filament:upgrade` refreshes assets and clears stale framework caches before the cache-rebuild commands. Do not run `composer update` or `npm update` as part of a production release installation.

### Rollback

If validation fails, re-enable maintenance mode if the app was resumed and stop background services. For Docker, restore the recorded previous image reference in the existing `.env` and recreate `app`, `queue` and `scheduler` in the same controlled sequence, retaining configuration and data volumes. For source/LAMP, restore the previous application build and its matching dependencies/assets, then rebuild caches and restart services.

v0.9.1 introduces no application schema migrations, but an image/code rollback does not undo data written after the update. Test rollback on the isolated upgrade environment. If restoring a backup is necessary, account for writes made since that backup instead of automatically replacing a live database.

The previous release contains the security issues fixed by v0.9.1. Treat a rollback as temporary recovery and plan the corrected security update before returning to normal operation.

Before a real go-live, complete the [Go-Live Checklist](go-live-checklist.md).
