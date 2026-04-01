# Rollenmodell (für MVP)

Das System nutzt ein Rollen- und Rechtesystem zur Trennung von Standard- und Administrationsfunktionen.

## Rollen
### Mitarbeiter
- Kann eigene Besuche anlegen und verwalten.
- Sichtbarkeit: nur Besuche, bei denen der Mitarbeiter der Ansprechpartner/Host ist (`host_user_id = eigener User`).

### Empfang / Zentrale
- Kann erwartete Besucher (Today-Queue) einsehen.
- Kann Check-in durchführen und Besucherausweis (PDF) bereitstellen/drucken.
- (Optional/Stretch) Kann Willkommensmonitor steuern.

### Admin
- Kann Benutzer verwalten.
- Kann Rollen/Rechte verwalten.
- Kann grundlegende Stammdaten verwalten (z.B. Abteilungen).

## Grundregeln (MVP)
- Mitarbeiter: nur eigene Besuche sichtbar/editierbar (sofern Status “geplant”).
- Empfang: Zugriff auf Today-Queue und Check-in/Badge-Funktionen.
- Admin: Zugriff auf alles.

