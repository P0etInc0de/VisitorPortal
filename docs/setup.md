````md
# Setup (Docker-first)

Dieses Projekt ist für lokale Entwicklung/Testbetrieb per Docker Compose ausgelegt (Apache + PHP + MySQL + Mailhog).  
Laravel liegt im Ordner `backend/`.

## Voraussetzungen
- Docker Desktop installiert und gestartet
- Git installiert
- Repository lokal geklont

## Start (lokal)

1) Container bauen und starten
```bash
docker compose up -d --build
````

2. (Nur beim ersten Setup) Laravel-Projekt erzeugen

```bash
docker compose exec web composer create-project laravel/laravel backend
```

3. `.env` anlegen und konfigurieren

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

4. App-Key generieren

```bash
docker compose exec web php backend/artisan key:generate
```

5. Migrationen ausführen

```bash
docker compose exec web bash -lc "cd backend && php artisan migrate"
```

6. Browser öffnen

* App: [http://localhost:8080](http://localhost:8080)
* Mailhog UI: [http://localhost:8025](http://localhost:8025)

## Stop / Reset

Stoppen:

```bash
docker compose down
```

Stoppen und DB-Daten löschen (Achtung: löscht das MySQL-Volume):

```bash
docker compose down -v
```

## Konfiguration / häufige Stolperfallen

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
cd backend &&
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache &&
chmod -R 775 storage bootstrap/cache &&
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
"
docker compose exec web bash -lc "cd backend && php artisan view:clear"
```

```
::contentReference[oaicite:0]{index=0}
```
