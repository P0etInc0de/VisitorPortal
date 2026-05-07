
# Setup

Dieses Projekt ist für lokale Entwicklung/Testbetrieb per Docker Compose ausgelegt (Apache + PHP + MySQL + Mailhog).  
Laravel liegt im Ordner `backend/`.

## Voraussetzungen (Docker)
- Docker Desktop installiert und gestartet
- Git installiert
- Repository lokal geklont

## 1. `.env` anlegen und konfigurieren

* Datei: `backend/.env` (nicht committen)
* Falls noch nicht vorhanden:

```bash
cp backend/.env.example backend/.env
```

Wichtige Werte für Docker:

```env
APP_URL=http://localhost:8080
APP_TIMEZONE=Europe/Berlin

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

# For PDF Badge (Gotenberg in Docker):
GOTENBERG_URL=http://gotenberg:3000

#GitHub Link
GITHUB_REPO_URL="https://github.com/P0etInc0de/Besucherportal"
```

## 2. Web-Container bauen und starten
```bash
docker compose build web
````

## 3. Installiere alle PHP-Abhängigkeiten, die in composer.json definiert sind

```bash
docker compose run --rm web composer install
```

## 4. Alle container bauen und starten

```bash
docker compose up -d --build
```

Ergebnis: Alle Laravel-Pakete sind im Container verfügbar.

## 5. App-Key generieren

```bash
docker compose exec web php artisan key:generate
```

## 6. Migrationen ausführen

```bash
docker compose exec web bash -lc "php artisan migrate"
```

## 6.1 Test-Datenbank erstellen

```bash
docker compose exec db mysql -u root -proot_pw -e "CREATE DATABASE IF NOT EXISTS besucherportal_test;"
```

## 7. Datenbank mit Test-Daten befüllen (Seeding)

```bash
docker compose exec web bash -lc "php artisan migrate:fresh --seed"
```

## 8. Browser öffnen

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

## 9. Login/Standard Accounts

Folgende Accounts existieren:

- admin@demo.local
- empfang@demo.local
- mitarbeiter@demo.local
- manager@demo.local
- welcomemonitor@demo.local

Das standard Passwort ist: "Passwort123!"

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

## Frontend-Assets / Vite

Für aktive CSS-/JavaScript-Entwicklung kann der Vite Dev Server genutzt werden. Dann werden Assets über `localhost:5173` geladen und Hot Reloading ist aktiv.

Für UI-Bewertung, Demos und Präsentationen sollte der Build-Modus genutzt werden, da der Vite Dev Server bei schnellen Reloads leichter abgebrochene Asset-Requests verursachen kann.

Build-Modus aktivieren:

```bash
cd backend
rm -f public/hot
npm run build
docker compose exec web php artisan optimize:clear
docker compose exec web php artisan view:clear
```

Falls der Node/Vite-Service läuft:

```bash
docker compose stop node
```

Oder ein direkt gestartetes `npm run dev`-Terminal mit `Ctrl+C` beenden.

Prüfung im Browser-Network-Tab:

* Erwartet: `localhost:8080/build/assets/app-....css` und `localhost:8080/build/assets/app-....js`
* Nicht erwartet: `localhost:5173/app.css`, `localhost:5173/app.js`, `localhost:5173/client`

Hinweis: Extremes Reload-Spamming kann weiterhin Browser-Requests abbrechen (`NS_BINDING_ABORTED`). Das ist dann kein normales UI-/Theme-Problem, sondern ein abgebrochener Browser-Request.

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
