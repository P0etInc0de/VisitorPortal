# Rollenmodell (für MVP)

Das System nutzt ein Rollen- und Rechtesystem zur Trennung von Standard- und Administrationsfunktionen.

## Rollen
### Mitarbeiter
- Kann eigene Besuche anlegen und verwalten.
- Sichtbarkeit: nur Besuche, bei denen der Mitarbeiter der Ansprechpartner/Host ist (`host_user_id = eigener User`).
- Kann selbst angelegte oder eigenen Besuchen zugeordnete Besucher verwenden, aber kein globales Besucherarchiv einsehen.

### Empfang / Zentrale
- Kann erwartete Besucher am eigenen Standort einsehen.
- Kann Check-in durchführen und Besucherausweis (PDF) bereitstellen/drucken.
- Kann bekannte Besucher für Empfangsprozesse am eigenen Standort verwenden.
- Kann Willkommensmonitore am eigenen Standort steuern.

### Admin
- Kann Benutzer verwalten.
- Kann Rollen/Rechte verwalten.
- Kann grundlegende Stammdaten verwalten (z.B. Abteilungen).

## Grundregeln (MVP)
- Mitarbeiter: nur eigene Besuche sichtbar/editierbar (sofern Status “geplant”).
- Mitarbeiter: keine globale Visitor-Directory-Berechtigung und keine automatische Stammdatenänderung fremder Besucher.
- Empfang: Zugriff auf Standort-Queue und Check-in/Badge-Funktionen am eigenen Standort.
- Admin: Zugriff auf alles.

## Standortregeln
- `Site` bedeutet Standort, Empfangsbereich, Werk oder Gebäude innerhalb einer Organisation.
- `visits.site_id` wird direkt gespeichert und nicht nur aus dem Host abgeleitet, damit historische Besuche stabil bleiben, wenn Benutzer später Standort oder Abteilung wechseln.
- `departments`, `users`, `visits`, `monitors` und `recurring_visit_series` sind standortbezogen.
- `visitors` bleiben organisationsweit, um Dubletten derselben externen Person über mehrere Standorte zu vermeiden.
- Vertrauliche Besuche werden nie automatisch auf Willkommensmonitoren angezeigt.
