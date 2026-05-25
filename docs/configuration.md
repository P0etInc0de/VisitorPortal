# Configuration

VisitorPortal is configured through environment files. Do not commit real `.env` files or secrets.

## Environment Files

- `.env.demo.example`: template for the released demo stack. The scripts create `.env.demo` from it.
- `backend/.env.example`: local development template for `docker-compose.yml`.
- `backend/.env.production.example`: production-oriented template. For Docker production, copy it to `.env` in the repository root next to `docker-compose.prod.yml`. For LAMP deployments, use it as `backend/.env`.

## Version Baseline

Documentation should refer to supported major versions, not `latest`:

- PHP 8.4
- Node.js 24
- MariaDB 11.4
- Laravel 12
- Gotenberg 8.32

Current dependency patches are controlled by `composer.lock`, `package-lock.json` and the Docker image build.

## Core Variables

Application:

- `APP_ENV`: `local` for development/demo, `production` for production.
- `APP_DEBUG`: must be `false` in production.
- `APP_KEY`: required secret; generate once and keep it stable.
- `APP_URL`: canonical external URL, including HTTPS in production.
- `APP_LOCALE`, `APP_FALLBACK_LOCALE`: UI locale defaults.

Database:

- `DB_CONNECTION=mariadb`
- `DB_HOST=db` in Docker, not `127.0.0.1`.
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `DB_ROOT_PASSWORD` for the production MariaDB container.

Mail:

- `MAIL_MAILER=smtp`
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

Queue and health:

- `QUEUE_CONNECTION=database`
- `DB_QUEUE_RETRY_AFTER=180`
- `HEALTH_CACHE_STORE=database`
- `HEALTH_QUEUE_STALE_AFTER=300`
- `HEALTH_SCHEDULER_STALE_AFTER=300`
- `HEALTH_HEARTBEAT_TTL=600`

## Authentication And MFA

Local authentication uses Laravel Fortify. MFA is enabled by default and required for privileged roles.

Recommended production defaults:

```env
MFA_ENABLED=true
MFA_OPTIONAL_FOR_USERS=true
APP_MFA_REQUIRED_ROLES=admin,super_admin
APP_MFA_REQUIRED_FOR_AUTH_METHODS=local
APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS=local,sso
APP_MFA_SESSION_TTL_MINUTES=720
```

`APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` requires an App-MFA-confirmed session for `/admin`. It does not prompt again if App-MFA was already confirmed in the current web session. The value `sso` means VisitorPortal also requires local App-MFA after SSO login before privileged admin-panel access.

`MFA_ENABLED=false` disables role-based enforcement, but users with already configured MFA are still challenged during login.

## Single Sign-On

SSO is disabled by default.

```env
AUTH_MODE=local
SSO_ENABLED=false
SSO_DRIVER=oidc
OIDC_PROVISIONING_MODE=disabled
OIDC_SYNC_ROLES=false
```

Supported `AUTH_MODE` values:

- `local`: local login only.
- `local_and_sso`: local login and OIDC login.
- `sso_only`: SSO for normal users; local login only for accounts with `LoginLocallyInSsoOnlyMode`.

See [Single Sign-On](sso.md) before enabling SSO in production.

## Privacy And Retention

Visitor contact fields are optional by default.

```env
VISITOR_CONTACT_REQUIREMENT=optional
PRIVACY_VISIT_RETENTION_DAYS=365
PRIVACY_PURGE_ENABLED=true
PRIVACY_NOTIFICATION_RETENTION_DAYS=365
PRIVACY_WALK_IN_CONFIDENTIAL_DEFAULT=true
PRIVACY_NOTICE_URL=
```

Supported `VISITOR_CONTACT_REQUIREMENT` values:

- `optional`
- `require_one`
- `require_email`
- `require_phone`

Technical retention is separate from visit retention:

```env
PRIVACY_TECHNICAL_RETENTION_ENABLED=true
PRIVACY_SESSION_RETENTION_DAYS=30
PRIVACY_FAILED_JOB_RETENTION_DAYS=30
PRIVACY_JOB_BATCH_RETENTION_DAYS=30
PRIVACY_LOG_RETENTION_DAYS=30
PRIVACY_HEALTH_CACHE_RETENTION_DAYS=7
```

`PRIVACY_NOTICE_URL` is an operator-owned privacy notice URL. VisitorPortal is not legal advice and does not make a deployment privacy-compliant by itself.

## Organization And Sites

One VisitorPortal instance is intended for one legal organization. Multiple physical locations can be modeled as sites.

- Users have a primary site and can be assigned to additional sites.
- Visits, monitors, reception views and recurring visit series are site-scoped.
- Visitors are organization-wide master records to reduce duplicates.
- For legally separate organizations, run separate instances unless full multi-tenancy has been implemented and audited.

## Branding And Welcome Monitor

Branding values are configured in `backend/config/branding.php` and can be overridden with environment variables:

- `BRANDING_NAME`
- `BRANDING_LOGO_LIGHT`
- `BRANDING_LOGO_DARK`
- `BRANDING_MAIL_LOGO`
- `BRANDING_BADGE_LOGO`
- `BRANDING_BADGE_DESIGN`
- `BRANDING_BADGE_ACCENT_COLOR`
- `BRANDING_MONITOR_FALLBACK_HEADING`
- `BRANDING_MONITOR_FALLBACK_SUBHEADING`
- `BRANDING_MONITOR_SLIDE_HEADING`
- `BRANDING_MONITOR_AUTO_GENERATION=false`
- `BRANDING_MONITOR_DISPLAY_MODE=title_first_initial_last_name`

Automatic welcome-monitor generation is disabled by default for privacy. See [Welcome Monitor](welcome_monitor.md).

## Upload Guardrails

Monitor and branding images are public display assets, not private document storage.

```env
UPLOAD_IMAGE_MAX_SIZE_KB=20480
UPLOAD_IMAGE_MAX_WIDTH=20000
UPLOAD_IMAGE_MAX_HEIGHT=20000
UPLOAD_IMAGE_MAX_PIXELS=150000000
```

JPEG, PNG and WebP uploads are validated and normalized before storage. SVG uploads are not accepted through the monitor upload forms.

## Cache After Changes

In development, avoid `config:cache` unless you are explicitly testing production behavior.

After changing `.env` in production, rebuild caches:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

In Docker production, run the same commands through the `app` service:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
```
