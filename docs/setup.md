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

Beim ersten Start erstellt das Skript eine lokale `.env.demo` aus `.env.demo.example`. Falls diese Ports belegt sind, koennen `APP_PORT` und `MAILHOG_PORT` in `.env.demo` angepasst werden.

Login:

- `admin@example.org`
- Passwort: `ChangeMe-42!`

Weitere Demo-Accounts:

- `reception@example.org`
- `employee@example.org`
- `manager@example.org`
- `welcome@example.org`
- `security@example.org`

Alle verwenden `ChangeMe-42!`.

Die Demo-Daten nutzen ausschließlich reservierte `example.org`-, `example.com`- und `example.net`-E-Mail-Domains. Telefonnummern sind entweder leer oder als eindeutig fiktive E.164-Nummern aus reservierten Medien-/Demo-Bereichen hinterlegt. Zusätzlich zu den festen Demo-Accounts werden englische Faker-Daten für weitere Benutzer, Besucher und Besuche erzeugt. Demo-Seeding ist in `APP_ENV=production` blockiert.

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

Das Demo-Setup liest die lokale `.env.demo`. Wenn sie fehlt, wird sie aus `.env.demo.example` erstellt.

Im Source-Repository steht in `.env.demo.example` `RELEASE_VERSION_PLACEHOLDER`. Der Release-ZIP-Workflow ersetzt diesen Platzhalter beim Veroeffentlichen automatisch durch den Release-Tag, zum Beispiel:

```env
VISITORPORTAL_VERSION=v1.0.2
```

So bleiben Demos reproduzierbar und werden nicht versehentlich durch ein neues `latest`-Image veraendert. Wenn ein ZIP manuell erstellt wird, muss der Platzhalter vor dem Verpacken ersetzt werden.

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

## Running Artisan locally without Docker

Docker-first is the recommended setup. If you run Artisan directly in WSL/Ubuntu, the local PHP CLI must provide the required extensions.

Required examples:

- dom
- mbstring
- xml
- xmlwriter

Ubuntu/WSL:

```bash
sudo apt update
sudo apt install php-xml php-mbstring
```

For version-specific PHP installations:

```bash
sudo apt install php8.3-xml php8.3-mbstring
```

Check:

```bash
php -m | grep -E 'dom|mbstring|xml|xmlwriter'
```

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
