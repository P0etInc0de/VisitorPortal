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

5. Migrationen ausführen (optional inkl. Seeds, falls vorhanden):

```bash
docker compose exec web php bash -lc "artisan migrate:fresh --seed"
```

## in progress...