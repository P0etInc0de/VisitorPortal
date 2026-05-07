# Besucherportal

Webbasiertes Besucherportal (Laravel) zur Planung und Abwicklung von Firmenbesuchen inkl. Check-in, Ausweisausgabe (PDF/Druck), Willkommensmonitor und Benachrichtigungen (THU-Teamprojekt).

## Ziel / Umfang (MVP)

- Besuch anlegen & verwalten (Mitarbeiter)
- Empfang: Today-Queue, Check-in, Ausweis als PDF druckbar
- Benachrichtigungen per E-Mail (Reminder am Besuchstag + „Gast ist eingetroffen“)
- Willkommensmonitor: Aktuelle Besuche auf einem externen Monitor zur Begrüßung anzeigen
- Rollen/Rechte: Mitarbeiter, Empfang/Zentrale, Admin (minimal)

## Projektstruktur

- Standard-Branch: `develop` (laufende Entwicklung)
- `main`: stabile, präsentierbare Stände (Meilenstein-/Release-Stände), versioniert über Tags/Releases
- `feature/`: neue feature
- `fix/`: bug-fixes
- `documents/`: Dokumentation

## Doku `docs/`

- [configuration.md](docs/configuration.md): Anleitung zur Konfiguration
- [decisions.md](docs/decisions.md): ADR (in Arbeit)
- [deployment.md](docs/deployment.md): Anleitung zum Deployment
- [mails.md](docs/mails.md): Dokumentation zum Mailversand
- [notifications.md](docs/notifications.md): Dokumentation zu Notifications
- [queue.md](docs/queue.md): Dokumentation zu Queues
- [roles.md](docs/roles.md): Dokumentation zu Rollen und Berechtigungen
- [scheduler.md](docs/scheduler.md): Dokumentation zum Scheduler
- [setup.md](docs/setup.md): Anleitung zur Einrichtung der Entwicklungsumgebung
- [UI.md](docs/UI.md): Dokumentation zur UI
- [update_workflow.md](docs/update_workflow.md)
- [welcome_monitor.md](docs/welcome_monitor.md): Anleitung zur Nutzung des Willkommenmonitors

### Diagramme `docs/diagrams`

- Enthält Diagramme um die Anwendung besser zu verstehen in Form von Bildern und `.uxf` Dateien. 
- Die `.uxf` Dateien können mit dem GPLv3 lizenzierten VSCode-Plugin "UMLet" angezeigt und editiert werden.

## Standard Accounts bei initialem Setup

Nachdem alles wie in der [setup.md](docs/setup.md) beschrieben eingerichtet wurde, kann man sich mit folgenden Accounts anmelden:

- admin@demo.local
- empfang@demo.local
- mitarbeiter@demo.local
- manager@demo.local
- welcomemonitor@demo.local

Das standard Passwort ist: "Passwort123!"