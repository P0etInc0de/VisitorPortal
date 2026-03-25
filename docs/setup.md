
# Setup

Dieses Projekt ist für lokale Entwicklung/Testbetrieb per Docker Compose ausgelegt (Apache + PHP + MySQL + Mailhog).  
Laravel liegt im Ordner `backend/`.

## Voraussetzungen (Docker)
- Docker Desktop installiert und gestartet
- Git installiert
- Repository lokal geklont

## 1. Container bauen und starten
```bash
docker compose up -d --build
````

## 2. `.env` anlegen und konfigurieren

* Datei: `backend/.env` (nicht committen)
* Falls noch nicht vorhanden:

```bash
cp backend/.env.example backend/.env
```

Wichtige Werte für Docker:

```env
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=visitorportal
DB_USERNAME=visitor
DB_PASSWORD=visitor_pw

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=visitorportal@example.test
MAIL_FROM_NAME="Besucherportal"
```

## 3. Installiere alle PHP-Abhängigkeiten, die in composer.json definiert sind

```bash
docker compose exec web bash -lc "composer install"
```

Ergebnis: Alle Laravel-Pakete sind im Container verfügbar.

## 4. App-Key generieren

```bash
docker compose exec web php artisan key:generate
```

## 5. Migrationen ausführen

```bash
docker compose exec web bash -lc "php artisan migrate"
```

## 6. Datenbank mit Test-Daten befüllen (Seeding)

```bash
docker compose exec web php bash -lc "artisan migrate:fresh --seed"
```

## 7. Browser öffnen

* App: [http://localhost:8080](http://localhost:8080)
* Mailhog UI: [http://localhost:8025](http://localhost:8025)

Optional: Container-Status prüfen

```bash
docker compose ps
```

## Stop / Reset

Stoppen:

```bash
docker compose down
```

Stoppen und DB-Daten löschen (Achtung: löscht das MySQL-Volume):

```bash
docker compose down -v
```

## Nützliche Standardbefehle

### Containerstatus

```powershell
docker compose ps
```

### Logs (alle)

```powershell
docker compose logs --tail=100
```

### Nur Node/Vite Logs

```powershell
docker compose logs --tail=100 node
```

### Nur Web/PHP Logs

```powershell
docker compose logs --tail=100 web
```

### Container stoppen

```powershell
docker compose down
```

### Container stoppen + DB zurücksetzen

Achtung: Löscht lokale DB-Daten (Volumes)

```powershell
docker compose down -v
```

## Cache leeren

### Clear: View, Config, Routes, Events, App-Cache

```bash
docker compose exec web php artisan optimize:clear
```

### View-Cache leeren

```bash
docker compose exec web php artisan view:clear
```

#### Config-Cache leeren

```bash
docker compose exec web php artisan config:clear
```

## Stolperfallen

* `.env`-Zeilen dürfen nicht auskommentiert sein (kein `# DB_HOST=...`). Sonst greifen Laravel-Defaults (z.B. `127.0.0.1` / `laravel`) und DB-Verbindungen schlagen fehl.
* Nach Änderungen an `.env` ggf. Config-Cache leeren (insbesondere wenn vorher `config:cache` genutzt wurde):

  * `php artisan config:clear`
  * `php artisan cache:clear`
  * Hinweis: In der Entwicklung `config:cache` möglichst nicht verwenden.
* In Docker ist `DB_HOST` der Service-Name aus `docker-compose.yml` (bei uns `db`), nicht `127.0.0.1`.
* Wenn `SESSION_DRIVER=database` aktiv ist, benötigt Laravel sofort eine funktionierende DB (sonst schlagen auch `cache:clear`/Logins etc. fehl).
* Wenn die Startseite einen Fehler mit `tempnam()` / Blade-Compile zeigt, fehlen meist Schreibrechte/Ordner:

```bash
docker compose exec web bash -lc "
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache &&
chmod -R 775 storage bootstrap/cache &&
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
"
docker compose exec web bash -lc "php artisan view:clear"
```

- Wenn Dropdowns/Buttons im Breeze-Layout (z.B. User-Menü/Logout) nicht reagieren: Breeze nutzt Alpine.js. Prüfen, ob Alpine installiert ist und in `resources/js/app.js` gestartet wird. Danach Vite neu starten.
  - Install: `npm install -D alpinejs @alpinejs/focus`
  - `resources/js/app.js` muss Alpine starten (siehe Repo-Stand)
  - Vite neu starten: `docker compose restart node`
