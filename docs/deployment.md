## LAMP (ohne Docker)

Die Anwendung soll zusätzlich zu Docker auch auf einem klassischen LAMP-Server (PHP + Webserver + Datenbank) lauffähig sein.

### Voraussetzungen

* PHP 8.x (Extensions: pdo_mysql, mbstring, intl, zip)
* Webserver: Apache oder Nginx
* MySQL 8.x (oder kompatibel)
* Composer verfügbar

### Deployment (Kurzablauf)

1. Projekt auf den Server kopieren (inkl. `backend/`).
2. DocumentRoot des Webservers auf `backend/public` setzen.
3. In `backend/` Abhängigkeiten installieren:

```bash
composer install --no-dev
```

4. `.env` anlegen/konfigurieren (DB/Mail/APP_URL) und Key generieren:

```bash
php artisan key:generate
```

5. Migrationen ausführen. Seeds sind in Produktion deaktiviert und dürfen nicht verwendet werden:

```bash
php artisan migrate --force
```

## Docker Produktion

- `docker-compose.prod.yml` ist für produktive Container gedacht.
- DB und Gotenberg werden nicht nach außen veröffentlicht.
- Mailhog ist nicht enthalten; SMTP muss über `MAIL_*` konfiguriert werden.
- `APP_KEY` ist Pflicht und hat keinen Default-Wert.
- `AUTO_MIGRATE=false` und `AUTO_SEED=false`; Migrationen laufen kontrolliert im Release-Prozess.
- Demo-Accounts und Demo-Seeds sind in `APP_ENV=production` blockiert.

## in progress...
