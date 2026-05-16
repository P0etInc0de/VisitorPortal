
# Konfiguration

Konfiguration erfolgt über `backend/.env`. Diese Datei wird nicht versioniert und darf nicht committed werden.

Vorlagen:
- `backend/.env.example` für lokale Entwicklung.
- `backend/.env.production.example` als sichere Orientierung für produktive Deployments.

## Wichtige Variablen (Docker / lokal)
- APP_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- VISITOR_CONTACT_REQUIREMENT (`optional`, `require_one`, `require_email`, `require_phone`)
- PRIVACY_VISIT_RETENTION_DAYS (Default: `365`)
- PRIVACY_PURGE_ENABLED (`true`/`false`)
- PRIVACY_SEARCH_RATE_LIMIT_PER_MINUTE (Default: `60`)

Docker-Hinweis:
- `DB_HOST` ist der Service-Name aus `docker-compose.yml` (bei uns `db`), nicht `127.0.0.1`.
- `MAIL_HOST` ist der Service-Name (bei uns `mailhog`), Port `1025`.

## Wichtige Variablen (LAMP / Server)
- APP_ENV=production
- APP_DEBUG=false
- APP_URL=<Produktiv-URL>
- DB_* (entsprechend Server-Datenbank)
- MAIL_* (SMTP Zugangsdaten des Unternehmens)

## Datenschutz und Kontaktpflicht
- Standard: Besucher benötigen weder E-Mail-Adresse noch Telefonnummer (`VISITOR_CONTACT_REQUIREMENT=optional`).
- Wenn fachlich erforderlich, kann genau ein Kontaktweg oder ein bestimmtes Feld erzwungen werden.
- Abgelaufene Besuche werden nach `PRIVACY_VISIT_RETENTION_DAYS` Tagen durch `visits:purge-expired` bereinigt, sofern kein Legal Hold gesetzt ist.
- Der Retention-Lauf unterstützt `--dry-run` für auditierbare Probeläufe ohne Datenänderung.
- Weitere Betreiberpflichten und eine Vorlage für Besucherinformationen stehen in `PRIVACY.md`.

## Cache-Hinweise
- In der Entwicklung `config:cache` möglichst nicht verwenden.
- Wenn `.env` geändert wurde:
```bash
php artisan config:clear
php artisan cache:clear
