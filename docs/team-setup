
````md
# Teamsetup (Windows PowerShell)

## Besucherportal lokal starten (Team-Setup)

Ziel: Projekt aus GitHub klonen und lokal per Docker Compose starten (inkl. Laravel, MySQL, Mailhog, Node/Vite).

## Voraussetzungen

- Git installiert
- Docker Desktop installiert
- Docker Desktop gestartet
- Docker Desktop läuft im Linux Container Mode
- Zugriff auf das private GitHub-Repository

## 1) Repository klonen

### Variante A (SSH, empfohlen wenn SSH-Key eingerichtet ist)

```powershell
cd C:\Users\<DEIN_NAME>
git clone git@github.com:P0etInc0de/Besucherportal.git
cd Besucherportal
````

### Variante B (HTTPS, falls kein SSH eingerichtet ist)

```powershell
cd C:\Users\<DEIN_NAME>
git clone https://github.com/P0etInc0de/Besucherportal.git
cd Besucherportal
```

## 2) Sicherstellen, dass wir auf `develop` sind

```powershell
git checkout develop
git pull
```

## 3) Docker-Container bauen und starten

```powershell
docker compose up -d --build
```

Erwartung:

* Container laufen für `web`, `db`, `mailhog`, `node`

Optional prüfen:

```powershell
docker compose ps
```

## 4) Laravel `.env` anlegen (lokal, nicht committen)

```powershell
Copy-Item backend\.env.example backend\.env
```

## 5) `backend/.env` anpassen (wichtig)

Datei öffnen: `backend/.env`

Diese Werte setzen bzw. prüfen (nicht auskommentiert! Bei "# DB_" das "#" entfernen!):

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

SESSION_DRIVER=database
```

Wichtig:

* `DB_HOST=db` (Service-Name aus Docker Compose, nicht `127.0.0.1`)
* Zeilen dürfen **nicht** mit `#` auskommentiert sein!
* Wenn Werte auskommentiert sind, verwendet Laravel sonst Standardwerte (z. B. `127.0.0.1`, `laravel`) und die DB-Verbindung schlägt fehl

## 6) App-Key generieren

```powershell
docker compose exec web php backend/artisan key:generate
```

## 7) Datenbankmigrationen ausführen

```powershell
docker compose exec web bash -lc "cd backend && php artisan migrate"
```

## 8) (Optional, aber sinnvoll) Laravel-Caches leeren

Erst **nach den Migrationen** ausführen (sonst kann bei `SESSION_DRIVER=database` ein Fehler kommen):

```powershell
docker compose exec web bash -lc "cd backend && php artisan optimize:clear"
```

## 9) Browser öffnen

* App: [http://localhost:8080](http://localhost:8080)
* Mailhog: [http://localhost:8025](http://localhost:8025)

## 10) Falls die Seite beim ersten Laden kurz unformatiert aussieht

Das kann beim ersten Start passieren, wenn Vite/Node noch hochfährt.

Dann:

* 120 Sekunden warten
* Seite neu laden (F5) / (STRG + F5)

Optional prüfen, ob der Node-Service läuft:

```powershell
docker compose logs --tail=50 node
```

Es sollte ungefähr sowas erscheinen:

* `VITE ... ready`
* `APP_URL: http://localhost:8080`

---

## Wichtige Hinweise (damit es bei allen gleich läuft)

### Nicht nötig / nicht machen

* Kein `composer create-project ...` ausführen
  (das Laravel-Projekt liegt bereits im Repository)
* `.env` nicht committen

### Reihenfolge ist wichtig

Wenn `SESSION_DRIVER=database` aktiv ist, braucht Laravel sofort eine funktionierende Datenbank.
Deshalb:

1. `.env` korrekt setzen
2. `key:generate`
3. `migrate`
4. erst danach `optimize:clear`

---

## Typische Fehler und Lösungen

### Docker wird nicht erkannt

Symptom:

* `docker` ist unbekannt / wird nicht erkannt

Lösung:

* Docker Desktop starten
* kurz warten, bis Docker vollständig hochgefahren ist
* PowerShell neu öffnen

### `dockerDesktopLinuxEngine` Pipe-Fehler

Symptom:

* Fehler mit `//./pipe/dockerDesktopLinuxEngine`

Lösung:

* Docker Desktop ist meist noch nicht vollständig gestartet
* kurz warten und Befehl erneut ausführen

### DB-Fehler trotz eingetragener Werte in `.env`

Häufige Ursachen:

* `.env`-Zeilen sind auskommentiert (`#DB_HOST=...`)
* `DB_HOST` ist falsch (muss in Docker `db` sein)
* alte Konfiguration hängt im Cache

Dann Cache leeren:

```powershell
docker compose exec web bash -lc "cd backend && php artisan config:clear && php artisan cache:clear"
```

Optional komplett:

```powershell
docker compose exec web bash -lc "cd backend && php artisan optimize:clear"
```

### Fehler mit `cache`-Tabelle / Session / Login

Ursache:

* `SESSION_DRIVER=database`, aber Migrationen wurden noch nicht ausgeführt

Lösung:

* zuerst Migrationen ausführen:

```powershell
docker compose exec web bash -lc "cd backend && php artisan migrate"
```

### Styles fehlen / Login-Seite sieht "roh" aus

Ursache:

* Node/Vite ist noch nicht fertig gestartet

Lösung:

* warten (120 Sekunden)
* Seite neu laden (F5) / (STRG + F5)
* Node-Logs prüfen:

```powershell
docker compose logs --tail=100 node
```

### `tempnam()` / Blade-Compile / Schreibrechte-Fehler

Wenn es Fehler zu `storage`, `bootstrap/cache`, Blade-Compile oder `tempnam()` gibt, dann im Container die Ordner/Rechte setzen:

```powershell
docker compose exec web bash -lc "
cd backend &&
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache &&
chmod -R 775 storage bootstrap/cache &&
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
"
```

Danach Views leeren:

```powershell
docker compose exec web bash -lc "cd backend && php artisan view:clear"
```

### Dropdowns / Logout im Breeze-Layout reagieren nicht

Mögliche Ursache:

* Alpine.js fehlt oder startet nicht korrekt

Prüfen:

* `resources/js/app.js` startet Alpine
* Alpine-Pakete sind vorhanden (je nach Repo-Stand)

Falls zwingend nötig (nicht so vorgesehen):

```powershell
docker compose exec node npm install -D alpinejs @alpinejs/focus
docker compose restart node
```

Hinweis:
* Im Normalfall sollte das bereits im Repo-Stand enthalten sein. Nur bei konkreten Problemen prüfen.

---

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

---

## Kurz-Checkliste (wenn etwas nicht funktioniert)

* Läuft Docker Desktop wirklich?
* Bin ich im richtigen Ordner (`Besucherportal`)?
* Bin ich auf `develop`?
* Existiert `backend/.env`?
* Sind die DB-Werte in `.env` korrekt und **nicht auskommentiert**?
* Ist `DB_HOST=db` gesetzt?
* Wurden Migrationen ausgeführt?
* Läuft `node` und ist Vite fertig gestartet?

