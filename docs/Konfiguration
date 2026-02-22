
```md
# Konfiguration

Konfiguration erfolgt über `backend/.env`. Diese Datei wird nicht versioniert und darf nicht committed werden.

## Wichtige Variablen (Docker / lokal)
- APP_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_FROM_ADDRESS, MAIL_FROM_NAME

Docker-Hinweis:
- `DB_HOST` ist der Service-Name aus `docker-compose.yml` (bei uns `db`), nicht `127.0.0.1`.
- `MAIL_HOST` ist der Service-Name (bei uns `mailhog`), Port `1025`.

## Wichtige Variablen (LAMP / Server)
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=<Produktiv-URL>
- DB_* (entsprechend Server-Datenbank)
- MAIL_* (SMTP Zugangsdaten des Unternehmens)

## Cache-Hinweise
- In der Entwicklung `config:cache` möglichst nicht verwenden.
- Wenn `.env` geändert wurde:
```bash
php artisan config:clear
php artisan cache:clear
