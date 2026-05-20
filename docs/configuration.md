
# Konfiguration

Konfiguration erfolgt über `backend/.env`. Diese Datei wird nicht versioniert und darf nicht committed werden.

Vorlagen:
- `.env.demo.example` fuer das Demo-Compose im Projektroot. Die Demo-Skripte erzeugen daraus lokal `.env.demo`.
- `backend/.env.example` für lokale Entwicklung.
- `backend/.env.production.example` als sichere Orientierung für produktive Deployments.
- Fuer Docker-Produktion wird diese Vorlage als `.env` im Projektroot neben `docker-compose.prod.yml` verwendet; fuer LAMP-Deployments als `backend/.env`.

## Wichtige Variablen (Docker / lokal)
- APP_URL
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- MFA_ENABLED (`true`/`false`)
- MFA_OPTIONAL_FOR_USERS (`true`/`false`; wenn `false`, koennen nicht verpflichtete Nutzer MFA nicht selbst aktivieren)
- APP_MFA_REQUIRED_ROLES (Default: `admin,super_admin`)
- APP_MFA_REQUIRED_FOR_AUTH_METHODS (Default: `local`)
- APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS (Default: `local,sso`)
- APP_MFA_SESSION_TTL_MINUTES (Default: `720`)
- AUTH_MODE (`local`, `local_and_sso`, `sso_only`)
- SSO_ENABLED (`true`/`false`)
- OIDC_ISSUER_URL, OIDC_CLIENT_ID, OIDC_CLIENT_SECRET
- OIDC_PROVISIONING_MODE (`disabled`, `invited_only`, `auto`)
- OIDC_SYNC_ROLES (`true`/`false`)
- VISITOR_CONTACT_REQUIREMENT (`optional`, `require_one`, `require_email`, `require_phone`)
- PRIVACY_VISIT_RETENTION_DAYS (Default: `365`)
- PRIVACY_PURGE_ENABLED (`true`/`false`)
- PRIVACY_SEARCH_RATE_LIMIT_PER_MINUTE (Default: `60`)
- PRIVACY_WALK_IN_CONFIDENTIAL_DEFAULT (Default: `true`)
- PRIVACY_NOTICE_URL (optional, vorbereitet für Betreiber-Dokumentation oder spätere externe Flows)
- BRANDING_MONITOR_AUTO_GENERATION (Default: `false`)
- BRANDING_MONITOR_DISPLAY_MODE (Default: `title_first_initial_last_name`)

Docker-Hinweis:
- `DB_HOST` ist der Service-Name aus `docker-compose.yml` (bei uns `db`), nicht `127.0.0.1`.
- `DB_CONNECTION` ist standardmaessig `mariadb`; Laravel nutzt dafuer intern den mysql-kompatiblen PDO-Treiber.
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
- `PRIVACY_NOTICE_URL` wird aktuell nicht automatisch in internen Mitarbeitenden-Formularen angezeigt. Wenn externe Besucher-Mails genutzt werden, enthalten diese den Link optional. Betreiber können Besucher zusätzlich außerhalb des Portals informieren, z. B. per Aushang, QR-Code, Website oder Einladung.

## Authentifizierung und MFA

- Lokale Authentifizierung laeuft ueber Laravel Fortify.
- MFA ist fuer normale Nutzer optional und fuer Rollen aus `APP_MFA_REQUIRED_ROLES` verpflichtend, wenn die Login-Art in `APP_MFA_REQUIRED_FOR_AUTH_METHODS` enthalten ist.
- Das Filament-Adminpanel verlangt eine App-MFA-bestaetigte Session fuer Rollen aus `APP_MFA_REQUIRED_ROLES`, wenn die Login-Art in `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` enthalten ist.
- `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` bedeutet nicht, dass bei jedem Wechsel in den Adminbereich erneut MFA abgefragt wird. Wurde App-MFA bereits beim Login oder durch eine App-MFA-Challenge in derselben Session bestaetigt, darf der Benutzer ohne zweite Abfrage zwischen Userbereich und Adminbereich wechseln.
- `APP_MFA_SESSION_TTL_MINUTES` begrenzt, wie lange eine lokale App-MFA-Bestaetigung innerhalb derselben Web-Session gueltig bleibt. Nach Ablauf muss die App-MFA-Challenge erneut bestanden werden; endet die Web-Session frueher, endet auch die App-MFA-Bestaetigung frueher.
- Der Wert `sso` in diesen `APP_MFA_*`-Settings bedeutet: VisitorPortal verlangt auch nach SSO-Login zusaetzlich lokale App-MFA. Das beschreibt nicht die MFA-Erwartung im externen Identity Provider.
- Listenartige `APP_MFA_*`-Settings koennen mit einem leeren Wert oder `none` bewusst deaktiviert werden.
- Der QR-Code und das Secret einer freiwilligen, noch unbestätigten Profil-MFA-Einrichtung werden weiterhin nur nach erneuter Passwortbestätigung angezeigt.
- Recovery Codes werden im Profil nur nach frischem, zweckgebundenem App-MFA-Step-up angezeigt. Für SSO-only Benutzer ist dafür keine lokale Passwortbestätigung erforderlich. Die Anzeige akzeptiert TOTP, aber keinen Recovery Code; die Regeneration akzeptiert TOTP oder Recovery Code.
- `MFA_ENABLED=false` deaktiviert die Rollen-Erzwingung, bestehende bestaetigte MFA wird beim Login aber weiterhin abgefragt.
- E-Mail-Verifikation ist in diesem Schritt bewusst nicht erzwungen. `MustVerifyEmail` ist nicht am User-Model aktiv.
- Optionales SSO laeuft ueber generisches OpenID Connect. Es ist standardmaessig deaktiviert und wird ueber `config/sso.php` sowie die Tabelle `user_identities` gesteuert.
- Externe SSO-Identitaeten werden ueber `provider + issuer + subject` verknuepft, nicht ueber E-Mail als dauerhaften Primaerschluessel.
- Auto-Provisioning und Rollen-Sync sind standardmaessig deaktiviert.
- Auto-provisionierte SSO-Nutzer erhalten `local_login_allowed=false`, damit lokale Passwortwege den IdP nicht umgehen.
- Details stehen in `docs/sso.md`.

Adminpanel-MFA-Beispiele:
- Lokaler Admin meldet sich mit Passwort und App-MFA an. Die Session ist bereits App-MFA-bestaetigt; der Wechsel in den Adminbereich erfordert keine zweite MFA-Abfrage.
- SSO-Admin meldet sich per SSO an. Wenn `sso` in `APP_MFA_REQUIRED_FOR_ADMIN_PANEL_AUTH_METHODS` enthalten ist und diese Session noch keine lokale VisitorPortal-App-MFA bestaetigt hat, erscheint beim Zugriff auf `/admin` eine App-MFA-Challenge.
- Admin ohne eingerichtete App-MFA wird beim Zugriff auf `/admin` durch die MFA-Einrichtung gefuehrt.
- Nach erfolgreicher App-MFA-Challenge darf der Benutzer innerhalb derselben Session zwischen Userbereich und Adminbereich wechseln, ohne erneut MFA einzugeben.

## Organisation und Standorte
- Eine Installation ist für genau eine Organisation/Firma gedacht.
- Mehrere Standorte innerhalb dieser Organisation werden über `sites` abgebildet.
- Benutzer haben einen Primärstandort (`users.site_id`) und zugeordnete Standorte (`site_user`). Ein Wechsel des Primärstandorts entfernt bestehende zusätzliche Standortzuordnungen nicht automatisch.
- Für rechtlich getrennte Organisationen sollten getrennte Instanzen betrieben werden, solange keine vollständige Multi-Tenancy explizit implementiert und auditiert wurde.
- Neue Installationen erhalten automatisch einen `Default Site`; bestehende Daten werden bei der Migration diesem Standort zugeordnet.
- Besucher bleiben organisationsweit als Person/Kontakt erhalten. Sichtbarkeit entsteht über berechtigte Besuche, Abteilungen und Standorte.

## Cache-Hinweise
- In der Entwicklung `config:cache` möglichst nicht verwenden.
- Wenn `.env` geändert wurde:
```bash
php artisan config:clear
php artisan cache:clear
