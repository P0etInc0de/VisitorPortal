# VisitorPortal

[Deutsch](README.de.md)

VisitorPortal is a web-based visitor management system for registering, managing and checking in company visitors.

It supports visitor pre-registration, reception workflows, printable visitor badges, a welcome monitor, notifications, and role-based access control.

## Project Status

VisitorPortal is a production-oriented visitor management system first developed as part of a student team project. Its implemented core workflows are functional and can support real-world visitor management scenarios, including visit registration, reception check-in, visitor badges, email notifications, role-based access control, and welcome monitor displays.

Although the implemented workflows are designed to be usable in practice, the project should not be deployed in a real organization without an additional security, privacy, and operational review.
The application processes potentially personal data such as visitor names, companies, contact details, visit times, and check-in/check-out timestamps. Before using it in a real organization, review legal requirements, retention policies, access control, logging, backups, and deployment security.

## Features

- Visitor pre-registration and visit management
- Reception dashboard for daily visits
- Visitor check-in and check-out
- Printable visitor badges as PDFs
- Check-in/out overview for reception desks
- Welcome monitor for scheduled visitors
- Role-based access control with custom permissions
- Admin panel for users, departments, roles, permissions, visitors, visits, monitors, and monitor slides
- Host notifications by email and in-app notifications
- Queue and scheduler support for background tasks
- Light, dark, system, and true black themes
- White-label branding via environment configuration
- Docker-based demo setup with prebuilt application image

## Organization And Site Model

VisitorPortal is designed for one organization per instance.
Multiple sites/locations within one organization are supported.
For legally separate organizations, run separate instances unless full multi-tenancy is explicitly implemented and audited.

Sites represent physical locations such as receptions, plants, offices, or buildings. Visits, monitors, reception views, and host/responsible user selections are scoped by site so one reception desk does not automatically see operational data from another location.

## Screenshots

### Reception Dashboard

HIER SCREENSHOT

### Check-In / Out

HIER SCREENSHOT

### Visit Details Or Visit Creation

HIER SCREENSHOT

### Visitor Badge

HIER SCREENSHOT

### Welcome Monitor

HIER SCREENSHOT

### Admin Panel

HIER SCREENSHOT


## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Livewire
- MySQL 8
- Blade
- Tailwind CSS 4
- daisyUI 5
- Alpine.js
- Filament 5
- Spatie Laravel Permission
- Filament Shield
- Spatie Laravel PDF
- Gotenberg for PDF generation
- Mailhog for local/demo email testing
- Docker and Docker Compose v2
- GitHub Actions

## Quick Start: Demo Setup

The demo setup is intended for non-technical users. It uses a prebuilt Docker image, so PHP, Composer, npm, and frontend build tools are not required on the host machine.

Requirements:

- Docker Desktop or Docker Engine with Docker Compose v2

Start the demo on Windows:

```bat
start.bat
```

Start the demo on macOS/Linux:

```bash
sh start.sh
```

After startup:

- Application: [http://localhost:8080](http://localhost:8080)
- Mailhog: [http://localhost:8025](http://localhost:8025)

If these ports are already in use, adjust `APP_PORT` and `MAILHOG_PORT` in `.env.demo`.

Stop the demo:

```bash
sh stop.sh
```

Reset demo data:

```bash
sh reset-demo.sh
```

Update the demo image:

```bash
sh update.sh
```

Windows users can use the matching `.bat` scripts.

For release ZIPs, `.env.demo` should pin a fixed image tag:

```env
VISITORPORTAL_VERSION=v1.0.2
```

## Demo Accounts

All demo accounts use the same password: `Passwort123!`

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@demo.local` | `Passwort123!` |
| Reception | `empfang@demo.local` | `Passwort123!` |
| Employee | `mitarbeiter@demo.local` | `Passwort123!` |
| Manager | `manager@demo.local` | `Passwort123!` |
| Welcome monitor | `welcomemonitor@demo.local` | `Passwort123!` |

The seeders also create additional fictional demo users, visitors, and visits. Do not use real person data in demo environments.

## Development Setup

The developer setup uses the source-mounted `docker-compose.yml`.

```bash
cp backend/.env.example backend/.env
docker compose run --rm web composer install
docker compose up -d --build
docker compose exec web php artisan key:generate
docker compose exec web php artisan migrate:fresh --seed
```

Application:

- [http://localhost:8080](http://localhost:8080)

Vite development server:

- [http://localhost:5173](http://localhost:5173)

More setup details are documented in [docs/setup.md](docs/setup.md).

## Tests And Quality Checks

Run the test suite inside the development container:

```bash
docker compose exec web php artisan test
```

Run a targeted test file:

```bash
docker compose exec web php artisan test tests/Feature/Receptionist/ReceptionAdministerVisitTest.php
```

Run Laravel Pint style checks:

```bash
docker compose exec web ./vendor/bin/pint --test -v
```

Build frontend assets:

```bash
docker compose run --rm node npm run build
```

The GitHub Actions workflow runs PHP setup, Pint, migrations, and tests against MySQL.

## Configuration And White Labeling

VisitorPortal has a central branding configuration in `backend/config/branding.php`.

Common environment variables:

- `BRANDING_NAME`
- `BRANDING_LOGO_LIGHT`
- `BRANDING_LOGO_DARK`
- `BRANDING_MAIL_LOGO`
- `BRANDING_BADGE_LOGO`
- `BRANDING_BADGE_DESIGN` (`standard` or `photo_qr`)
- `BRANDING_BADGE_ACCENT_COLOR`
- `BRANDING_MONITOR_FALLBACK_HEADING`
- `BRANDING_MONITOR_FALLBACK_SUBHEADING`
- `BRANDING_MONITOR_SLIDE_HEADING`

The default assets are stored in `backend/public/images/branding/`.

For production-like deployments, explicitly review these demo defaults:

- `APP_DEBUG`
- `APP_KEY`
- database credentials
- mail settings
- `AUTO_MIGRATE`
- `AUTO_SEED`
- `FORCE_SEED`
- `APP_URL`
- HTTPS/reverse proxy configuration

## Project Structure

- `backend/app/` - Laravel application code
- `backend/app/Livewire/` - Livewire components
- `backend/app/Filament/` - Filament admin resources
- `backend/resources/views/` - Blade views and UI partials
- `backend/resources/css/` - Tailwind/daisyUI entrypoint and themes
- `backend/database/migrations/` - database schema
- `backend/database/seeders/` - demo and setup seeders
- `backend/public/images/branding/` - default branding assets
- `docker/` - application Docker image and Apache/PHP configuration
- `docker-compose.yml` - source-mounted development setup
- `docker-compose.demo.yml` - prebuilt-image demo setup
- `.github/workflows/` - CI and image build workflows
- `docs/` - additional documentation and architecture notes

## Documentation

- [Setup Guide](docs/setup.md)
- [Configuration](docs/configuration.md)
- [Deployment](docs/deployment.md)
- [Roles And Permissions](docs/roles.md)
- [Queue](docs/queue.md)
- [Scheduler](docs/scheduler.md)
- [Mail](docs/mails.md)
- [Notifications](docs/notifications.md)
- [Welcome Monitor](docs/welcome_monitor.md)
- [UI Notes](docs/UI.md)
- [Architecture Decisions](docs/decisions.md)

## Roadmap

Possible next steps:

- Add a self-check-in workflow
- Add export and reporting features, for example CSV, calendar events, or contact exports
- Add full activity logging in the database, beyond technical file logs
- Harden authorization and security checks further
- Improve automated test coverage for edge cases and browser-level interactions
- Add more production deployment documentation
- Add data retention and privacy configuration options
- Improve onboarding and release documentation

## Security

Please read [SECURITY.md](SECURITY.md) before reporting vulnerabilities or deploying VisitorPortal in an environment with real visitor data.

Do not report sensitive security issues through public GitHub issues.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening larger pull requests.

## License

VisitorPortal is licensed under the GNU General Public License v3.0 or later. See [LICENSE](LICENSE).

## Authors And Credits

Developed by a student team at Technische Hochschule Ulm.

HIER EVTL. UNSERE NAMEN
personal names for public credits only after all involved people have agreed to be listed
