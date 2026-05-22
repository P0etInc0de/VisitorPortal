# Production Deployment

Diese Anleitung beschreibt den produktionssicheren Bootstrap ohne Demo-Daten. Demo-Seeder sind in `APP_ENV=production` gesperrt.

## Voraussetzungen

- PHP 8.4 mit `pdo_mysql`, `mbstring`, `intl`, `zip`, `fileinfo`.
- MariaDB 11.4 als Standarddatenbank. Laravel nutzt dafür den mysql-kompatiblen `pdo_mysql`-Treiber.
- Composer für LAMP-Deployments.
- Node.js 24 für LAMP-Deployments, wenn Frontend-Assets auf dem Zielsystem gebaut werden.
- Ein SMTP-Server für produktive E-Mails.
- Gotenberg 8.32 für Badge-PDFs.
- HTTPS vor der Anwendung, typischerweise über Reverse Proxy oder Webserver.
- Für Docker-Deployments: aktuelle Docker Engine oder Docker Desktop mit Docker Compose v2.

## Production Bootstrap

1. `.env` aus `backend/.env.production.example` ableiten und echte Secrets setzen. Für Docker-Deployments liegt diese `.env` im Projektroot neben `docker-compose.prod.yml`; für LAMP-Deployments liegt sie als `backend/.env` im Laravel-Verzeichnis.
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

## MFA Und Admin-Konten

Produktive Installationen sollten MFA aktiviert lassen. Standardmäßig müssen `admin` und `super_admin` MFA einrichten und bestätigen, bevor sie das Portal weiter nutzen können. Der Filament-Adminbereich prüft bestätigte MFA zusätzlich.

Empfohlene produktive Werte:

```env
MFA_ENABLED=true
MFA_OPTIONAL_FOR_USERS=true
APP_MFA_REQUIRED_ROLES=admin,super_admin
APP_MFA_REQUIRED_FOR_AUTH_METHODS=local
APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS=local,sso
APP_MFA_SESSION_TTL_MINUTES=720
```

`APP_MFA_REQUIRED_FOR_AUTH_METHODS` steuert, nach welchen Login-Arten VisitorPortal für diese Rollen lokale App-MFA verlangt. `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` steuert die zusätzliche App-MFA-Pflicht beim Zugriff auf das Admin Panel. Der Wert `sso` bedeutet hier, dass VisitorPortal auch nach SSO-Login zusätzlich lokale App-MFA verlangt, nicht dass MFA nur im externen Identity Provider erwartet wird. `APP_MFA_SESSION_TTL_MINUTES` begrenzt die lokale App-MFA-Bestätigung innerhalb der laufenden Web-Session; endet die Session früher, endet auch die App-MFA-Bestätigung früher.

Wenn ein Benutzer keinen Zugriff mehr auf seine Authenticator-App hat, kann ein Betreiber mit Shell-Zugriff MFA zuruecksetzen:

```bash
php artisan visitorportal:disable-mfa user@example.org
```

Der Command fragt standardmäßig nach Bestätigung. Für nicht-interaktive Runbooks kann bewusst `--force` genutzt werden.

Für produktive Umgebungen werden getrennte persönliche Konten für Alltagsarbeit und Administration empfohlen, z. B. `jane.doe@example.org` und `admin.jane.doe@example.org`.
Recovery Codes sollten direkt nach der Einrichtung sicher gespeichert werden. Die Anzeige im Profil erfordert später einen frischen, zweckgebundenen App-MFA-Step-up mit TOTP; ein Recovery Code reicht nicht aus, um bestehende Codes anzuzeigen. Die Regeneration akzeptiert TOTP oder einen Recovery Code als Recovery-Szenario. SSO-only Benutzer benötigen dafür keine lokale Passwortbestätigung.

## LAMP Deployment

1. Projekt auf den Server kopieren.
2. DocumentRoot auf `backend/public` setzen.
3. In `backend/` Dependencies installieren.

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
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
- `VISITORPORTAL_IMAGE` muss auf einen festen Image-Tag zeigen, z. B. `ghcr.io/p0etinc0de/besucherportal:v1.2.0`.
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
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --max-jobs=1000 --memory=128
```

Setze `DB_QUEUE_RETRY_AFTER=180`. Der Retry-After-Wert muss größer als der Worker-Timeout sein.

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

HSTS wird nur gesetzt, wenn Laravel die Anfrage als HTTPS erkennt. Falsche Proxy-Konfiguration kann HSTS und sichere Cookie-Erkennung verhindern. Teste Reverse-Proxy-Deployments explizit mit HTTPS-Ende-zu-Ende; wenn `TRUSTED_PROXIES` oder Forwarded-Header falsch sind, erkennt Laravel die Anfrage als HTTP und setzt HSTS/Secure-Cookie-Verhalten nicht korrekt.

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

`GOTENBERG_URL` muss für Badge-PDFs erreichbar sein. In Docker ist der Default `http://gotenberg:3000` und die Compose-Dateien verwenden Gotenberg 8.32.

## Storage Und Uploads

```bash
php artisan storage:link
```

Branding-, Logo- und Monitorbilder sind Anzeige-Assets. Je nach Storage-Disk und `storage:link` können hochgeladene Monitorbilder öffentlich unter `/storage/...` abrufbar sein. Betreiber dürfen dort keine vertraulichen, personenbezogenen oder sensiblen Informationen einbetten. JPEG-, PNG- und WebP-Uploads werden vor dem Speichern serverseitig validiert und von EXIF-/Metadaten bereinigt; SVG ist in den Monitor-Uploadformularen nicht erlaubt.

Die Upload-Grenzen sind bewusst großzügig für große Displays:

```env
UPLOAD_IMAGE_MAX_SIZE_KB=20480
UPLOAD_IMAGE_MAX_WIDTH=20000
UPLOAD_IMAGE_MAX_HEIGHT=20000
UPLOAD_IMAGE_MAX_PIXELS=150000000
```

Backups müssen `backend/storage` und die Datenbank enthalten.

## Retention

Besuchs- und Monitor-Retention läuft über:

```bash
php artisan visits:purge-expired --dry-run
php artisan visits:purge-expired
```

Der Scheduler führt den Command aus, wenn `PRIVACY_PURGE_ENABLED=true` gesetzt ist.

Technische Datenschutz-Retention läuft zusätzlich über:

```bash
php artisan privacy:purge-technical-data --dry-run
php artisan privacy:purge-technical-data
```

Der Scheduler führt diesen Command täglich aus, wenn `PRIVACY_TECHNICAL_RETENTION_ENABLED=true` gesetzt ist. Er bereinigt alte Database-Sessions, `failed_jobs`, `job_batches`, eindeutig abgelaufene Health-Heartbeat-Cache-Zeilen und alte Daily-Logdateien unter `storage/logs`. Er bereinigt keine externen Logs. Die Ausgabe enthält nur Zählwerte und keine Session-, Job- oder Log-Inhalte.

Konfigurierbare technische Retention:

```env
PRIVACY_TECHNICAL_RETENTION_ENABLED=true
PRIVACY_SESSION_RETENTION_DAYS=30
PRIVACY_FAILED_JOB_RETENTION_DAYS=30
PRIVACY_JOB_BATCH_RETENTION_DAYS=30
PRIVACY_LOG_RETENTION_DAYS=30
PRIVACY_HEALTH_CACHE_RETENTION_DAYS=7
```

Single-Log-Dateien wie `storage/logs/laravel.log` werden nicht blind gelöscht; nutze dafür Logrotation des Host-/Container-Betriebs. Backups, Docker-Volumes und Datenbank-Backups werden durch diesen Command nicht gelöscht. Externe Logsysteme, Reverse-Proxies, Docker-Daemon-Logs, Host-Systemlogs und SIEM-/Monitoring-Systeme brauchen eigene Retention-Regeln. `DATA_RETENTION_RUN_LOG_DAYS` steuert nur die Aufbewahrung der fachlichen Retention-Laufprotokolle.

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
- MFA für privilegierte Rollen aktiv
- Recovery Codes sicher gespeichert
- MFA-Reset-Prozess bekannt und auf Betreiber beschraenkt
- Queue Worker aktiv
- Scheduler aktiv
- Backups getestet
- Retention dry-run geprüft
- Demo-Seeding deaktiviert
