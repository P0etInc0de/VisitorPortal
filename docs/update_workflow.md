Sobald Laravel Pakete aktualisiert werden, muss auch das Admin Panel aktualisiert werden,
damit die neuesten Frontend-Assets (CSS/JS) korrekt in das öffentliche Verzeichnis kopiert, 
alte Caches geleert und die Benutzeroberfläche fehlerfrei gerendert wird:

```bash
docker compose exec web composer update
docker compose exec web php artisan filament:upgrade
```

Wenn ein neues Filament Plugin installiert wird muss filament mit aktualisiert werden:

```bash
docker compose exec web php artisan filament:upgrade
```