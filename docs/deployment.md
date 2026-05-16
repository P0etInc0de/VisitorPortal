# Production Deployment

Diese Anleitung beschreibt den produktionssicheren Bootstrap ohne Demo-Daten. Demo-Seeder sind in `APP_ENV=production` gesperrt.

## Voraussetzungen

- PHP 8.2+ mit `pdo_mysql`, `mbstring`, `intl`, `zip`, `fileinfo`.
- MySQL 8 oder kompatible Datenbank.
- Composer für LAMP-Deployments.
- Ein SMTP-Server für produktive E-Mails.
- Gotenberg für Badge-PDFs.
- HTTPS vor der Anwendung, typischerweise über Reverse Proxy oder Webserver.

## Production Bootstrap

1. `.env` aus `backend/.env.production.example` ableiten und echte Secrets setzen.
2. `APP_KEY` erzeugen und sicher speichern.
3. Migrationen ausführen.
4. Rollen und Permissions synchronisieren.
5. Initialen Admin interaktiv erstellen.

```bash
php artisan visitorportal:install
php artisan visitorportal:create-admin
```

Alternativ kann der Admin direkt im Installationslauf erstellt werden:

```bash
php artisan visitorportal:install --create-admin
```

Der Bootstrap erzeugt keine Demo-User und keine Standardpasswörter. Das Admin-Passwort wird interaktiv abgefragt.
Wenn `visitorportal:create-admin --force` für eine bestehende E-Mail genutzt wird, bestätigt der Operator zusätzlich, dass Passwort, Profildaten, Standortzuordnung und Admin-Rolle überschrieben werden.

## Einzelne Bootstrap-Commands

```bash
php artisan visitorportal:sync-permissions
php artisan visitorportal:create-admin
php artisan visitorportal:install
```

`visitorportal:sync-permissions` ist idempotent und kann nach Updates erneut ausgeführt werden.

## LAMP Deployment

1. Projekt auf den Server kopieren.
2. DocumentRoot auf `backend/public` setzen.
3. In `backend/` Dependencies installieren.

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan visitorportal:install
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

4. Webserver-User muss Schreibrechte auf `backend/storage` und `backend/bootstrap/cache` haben.
5. Queue Worker dauerhaft betreiben.
6. Scheduler per Cron oder systemd betreiben.

## Docker Produktion

- `docker-compose.prod.yml` ist für produktive Container gedacht.
- DB und Gotenberg werden nicht nach außen veröffentlicht.
- Mailhog ist nicht enthalten; SMTP muss über `MAIL_*` konfiguriert werden.
- `APP_KEY` ist Pflicht und hat keinen Default-Wert.
- `AUTO_MIGRATE=false` und `AUTO_SEED=false` sind sichere Defaults.
- `AUTO_SEED=true` ist in Production blockiert. Nutze stattdessen `visitorportal:install`.

Typischer Ablauf:

```bash
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:install
docker compose -f docker-compose.prod.yml exec app php artisan visitorportal:create-admin
```

## Queue Worker

Produktiv muss ein Queue Worker laufen, weil Benachrichtigungen und geplante Aufgaben Jobs nutzen können.

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

## Scheduler

Der Scheduler muss jede Minute laufen.

```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Docker nutzt dafür den `scheduler` Service mit `php artisan schedule:work`.

## Reverse Proxy Und TLS

- Setze `APP_URL=https://...`.
- Setze `SESSION_SECURE_COOKIE=true`.
- Setze `SESSION_ENCRYPT=true`.
- Setze `TRUSTED_PROXIES` nur auf die Proxy-IP oder ein internes Proxy-Netz.
- `TRUSTED_PROXIES=*` ist nur für kontrollierte Container-/Proxy-Netze geeignet.
- Der Proxy muss `X-Forwarded-Proto`, `X-Forwarded-Host`, `X-Forwarded-Port` und `X-Forwarded-For` korrekt setzen.

HSTS wird nur gesetzt, wenn Laravel die Anfrage als HTTPS erkennt. Falsche Proxy-Konfiguration kann HSTS und sichere Cookie-Erkennung verhindern.

## Content Security Policy

Die Standard-CSP ist mit Livewire, Alpine.js und Filament kompatibel. Sie ist bewusst keine strikt gehärtete CSP, weil `unsafe-inline` und `unsafe-eval` aktuell für Framework-Kompatibilität erlaubt sind.

Aktueller Modus:

```text
default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: blob:; font-src 'self' data:; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; connect-src 'self'
```

Eine strengere CSP sollte zuerst im Report-Only-Modus getestet werden.

## Mail

Produktiv muss `MAIL_MAILER=smtp` mit echten SMTP-Daten gesetzt sein. Teste nach dem Bootstrap Passwort-Reset und Besucher-/Host-Mails in einer Staging-Umgebung.

## Gotenberg

`GOTENBERG_URL` muss für Badge-PDFs erreichbar sein. In Docker ist der Default `http://gotenberg:3000`.

## Storage Und Uploads

```bash
php artisan storage:link
```

Backups müssen `backend/storage` und die Datenbank enthalten.

## Retention

Besuchs- und Monitor-Retention läuft über:

```bash
php artisan visits:purge-expired --dry-run
php artisan visits:purge-expired
```

Der Scheduler führt den Command aus, wenn `PRIVACY_PURGE_ENABLED=true` gesetzt ist.

Technische Daten müssen Betreiber zusätzlich berücksichtigen:

```bash
php artisan session:prune
php artisan queue:prune-failed
```

Logs und Backups brauchen eigene Aufbewahrungsfristen außerhalb der Anwendung. `DATA_RETENTION_RUN_LOG_DAYS` steuert die Aufbewahrung der Retention-Laufprotokolle.

## Datenschutzinformation

`PRIVACY_NOTICE_URL` ist vorbereitet, wird aber in internen Mitarbeitenden-Formularen nicht automatisch angezeigt. Wenn externe Besucher-Mails produktiv genutzt werden, enthalten diese den Link optional. Betreiber können Besucher zusätzlich außerhalb des Portals informieren, z. B. per Aushang, QR-Code, Website, Einladung oder Papierhinweis. Eine Vorlage liegt in `docs/data-protection-notice-template.md`.

## Updates

1. Backup erstellen.
2. Wartungsfenster aktivieren.
3. Code/Image aktualisieren.
4. Migrationen ausführen.
5. Permissions synchronisieren.
6. Caches neu bauen.
7. Queue Worker neu starten.

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

## Hardening Checklist

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` gesetzt und geheim
- HTTPS aktiv
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `TRUSTED_PROXIES` korrekt gesetzt
- SMTP produktiv konfiguriert
- Queue Worker aktiv
- Scheduler aktiv
- Backups getestet
- Retention dry-run geprüft
- Demo-Seeding deaktiviert
