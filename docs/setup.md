# Setup

Dieses Projekt bietet zwei Setup-Wege:

- **Demo-Setup fuer normale Nutzer:** ein Script starten, fertig.
- **Entwickler-Setup:** Source-Code mounten, Vite Dev Server nutzen, selbst bauen.

## Demo-Setup: ein Befehl

Voraussetzung:

- Docker Desktop ist installiert und gestartet.

Windows:

```bat
start.bat
```

macOS/Linux:

```bash
sh start.sh
```

Das Script macht automatisch:

- Docker-Images herunterladen
- Container starten
- Datenbankverbindung abwarten
- Migrationen ausfuehren
- Demo-Daten laden
- Queue und Scheduler starten

Nach erfolgreichem Start:

- App: [http://localhost:8080](http://localhost:8080)
- Mailhog UI: [http://localhost:8025](http://localhost:8025)

Falls diese Ports belegt sind, koennen `APP_PORT` und `MAILHOG_PORT` in `.env.demo` angepasst werden.

Login:

- `admin@demo.local`
- Passwort: `Passwort123!`

Weitere Demo-Accounts:

- `empfang@demo.local`
- `mitarbeiter@demo.local`
- `manager@demo.local`
- `welcomemonitor@demo.local`

Alle verwenden `Passwort123!`.

## Demo stoppen

Windows:

```bat
stop.bat
```

macOS/Linux:

```bash
sh stop.sh
```

Die Daten bleiben dabei erhalten.

## Demo zuruecksetzen

Loescht lokale Demo-Datenbank und Storage-Volume und startet frisch.

Windows:

```bat
reset-demo.bat
```

macOS/Linux:

```bash
sh reset-demo.sh
```

## Demo aktualisieren

Windows:

```bat
update.bat
```

macOS/Linux:

```bash
sh update.sh
```

## Version pinnen

Das Demo-Setup liest `.env.demo`.

Fuer Release-Zips sollte dort ein fester Image-Tag stehen, zum Beispiel:

```env
VISITORPORTAL_VERSION=v1.0.2
```

So bleiben Demos reproduzierbar und werden nicht versehentlich durch ein neues `latest`-Image veraendert.

## Auto-Setup steuern

Fuer lokale Demos sind automatische Migrationen und Seed-Daten gewollt:

```env
AUTO_MIGRATE=true
AUTO_SEED=true
```

Fuer echten produktiven Betrieb sollte das bewusst gesteuert werden, z. B.:

```env
AUTO_MIGRATE=false
AUTO_SEED=false
```

Migrationen koennen dann kontrolliert ausgefuehrt werden:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml exec app php artisan migrate --force
```

## Was laeuft in Docker?

Das Demo-Compose startet:

- App-Container mit Apache/PHP/Laravel
- MySQL 8
- Queue Worker
- Scheduler
- Mailhog
- Gotenberg fuer PDF-Ausweise

Persistente Volumes:

- `dbdata`: MySQL-Daten
- `app_storage`: Laravel Storage

## Entwickler-Setup

Das bestehende `docker-compose.yml` ist weiterhin fuer Entwicklung gedacht:

```bash
cp backend/.env.example backend/.env
docker compose run --rm web composer install
docker compose up -d --build
docker compose exec web php artisan key:generate
docker compose exec web php artisan migrate:fresh --seed
```

App:

- [http://localhost:8080](http://localhost:8080)

Vite Dev Server:

- [http://localhost:5173](http://localhost:5173)

## Nuetzliche Docker-Befehle

Containerstatus:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml ps
```

App-Logs:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs --tail=120 app
```

Alle Logs:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs --tail=120
```

## Stolperfallen

- Docker Desktop muss wirklich laufen.
- Standard-Port `8080` muss frei sein, oder `APP_PORT` muss in `.env.demo` angepasst werden.
- Standard-Port `8025` muss frei sein, oder `MAILHOG_PORT` muss in `.env.demo` angepasst werden.
- Wenn das App-Setup fehlschlaegt, zuerst die App-Logs pruefen:

```bash
docker compose --env-file .env.demo -f docker-compose.demo.yml logs --tail=120 app
```

Der Entrypoint loggt die Schritte bewusst deutlich, z. B. Datenbankcheck, Migrationen und Seed-Daten.
