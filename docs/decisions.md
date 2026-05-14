# Architecture Decisions (ADR)

Kurze Architekturentscheidungen und Begründungen für das Projekt **VisitorPortal**.

## ADR-001 Projekt- und Sprachkonventionen

- **Datum:** 2026-03-03
- **Status:** Accepted
- **Entscheidung:**
  - Entwicklungskonventionen primär auf **Englisch**
    - Code: Klassen, Variablen, Methoden
    - Branches, Commits, Pull Requests
    - technische Tickets
  - Dokumentation und UI-Texte primär auf **Deutsch**
  - Mehrsprachigkeit grundsätzlich vorgesehen, initial jedoch Fokus auf **Deutsch** für UI und Projektdokumentation
- **Begründung:**
  - Laravel-Dokumentation, Fehlermeldungen und Community-Ressourcen sind überwiegend englischsprachig
  - Debugging, Recherche und Troubleshooting werden dadurch erleichtert
  - Der Auftraggeber ist deutschsprachig, daher sind deutsche UI-Texte und Dokumentation für Verständlichkeit und Abnahme sinnvoll
  - Zusätzlich bevorzugt der Auftraggeber englischsprachigen Code, da auch Entwickler im Ausland beteiligt sind
- **Quellen:**
  - Laravel Documentation: https://laravel.com/docs

---

## ADR-002 Rollenbasiertes Berechtigungsmodell

- **Datum:** 2026-03-05
- **Status:** Accepted
- **Entscheidung:**
  - Einsatz von **spatie/laravel-permission** als Basis für Rollen und Berechtigungen
  - Verwaltung der Rollen im Admin-Panel über Filament Shield
- **Begründung:**
  - etablierte Standardlösung im Laravel-Umfeld
  - **MIT-lizenziert**
  - aktiv gepflegt und gut dokumentiert
  - gute Integration mit Laravel **Gate**, **Blade-Directives** und **Policies**
  - flexibler und wartbarer als eine selbst entwickelte einfache Rollenlogik
  - geeignet für eine schlanke, erweiterbare Rechteverwaltung in VisitorPortal
- **Quellen:**
  - Spatie Laravel Permission, Introduction: https://spatie.be/docs/laravel-permission/v7/introduction
  - Spatie Laravel Permission, Blade Directives: https://spatie.be/docs/laravel-permission/v7/basic-usage/blade-directives
  - Spatie Laravel Permission, Using Policies: https://spatie.be/docs/laravel-permission/v7/best-practices/using-policies
  - GitHub Repository: https://github.com/spatie/laravel-permission
  - Filament Shield: https://filamentphp.com/plugins/bezhansalleh-shield

---

## ADR-003 Docker-first Development Setup

- **Datum:** 2026-03-19
- **Status:** Accepted
- **Entscheidung:**
  - Entwicklung und lokales Setup erfolgen **Docker-first**
  - Das Projekt wird standardmäßig über **Docker Compose** gestartet und betrieben
- **Begründung:**
  - vom Auftraggeber **optional, aber bevorzugt**
  - einheitliches Entwicklungssetup für alle Teammitglieder
  - geringere Unterschiede zwischen lokalen Umgebungen
  - schnellere Einarbeitung und reproduzierbarer Projektstart
  - Services wie Webserver, Datenbank und weitere Abhängigkeiten lassen sich gemeinsam definieren und mit einem Befehl starten
  - Containerisierung vereinfacht Übergabe, Wartung und spätere Weiterentwicklung
  - die Vorteile wurden vom Team erkannt und bewusst in die Projektumsetzung übernommen
- **Quellen:**
  - Docker Compose Overview: https://docs.docker.com/compose/
  - Why use Compose?: https://docs.docker.com/compose/intro/features-uses/
  - How Compose works: https://docs.docker.com/compose/intro/compose-application-model/
  - Compose file reference: https://docs.docker.com/reference/compose-file/

---

## ADR-004 UI-Stack (Projektstandard)

- **Datum:** 2026-03-19
- **Status:** Accepted
- **Entscheidung:**
  - Einsatz von **daisyUI** als Component Library
  - **kein Livewire-first-Ansatz** als Projektstandard
  - Standard-Stack für die Oberfläche:
    - **Laravel Blade**
    - **Tailwind CSS**
    - **daisyUI**
- **Begründung:**
  - konsistente und wiederverwendbare UI-Bausteine für Formulare, Tabellen, Cards, Modals und Alerts
  - geringerer Implementierungsaufwand gegenüber einer vollständigen Eigenentwicklung der UI-Komponenten
  - **daisyUI** ist **MIT-lizenziert**, aktiv gepflegt und gut mit Laravel nutzbar
  - daisyUI ist als Tailwind-CSS-Plugin allgemeiner und weniger bindend als eine Livewire-spezifische Lösung
  - dadurch bleibt die UI-Schicht einfacher, flexibler und wartungsärmer
  - **Mary UI** war ein Favorit, wurde aber nicht gewählt, da es auf **Livewire** ausgerichtet ist und damit eine stärkere Architekturbindung erzeugt
  - Es wurde bewusst **gegen Livewire als Projektstandard** entschieden, um zusätzliche Architekturkomplexität zu vermeiden
  - Für VisitorPortal ist ein Standard mit Blade + Tailwind + daisyUI ausreichend und für das MVP zweckmäßig
- **Quellen:**
  - daisyUI Homepage: https://daisyui.com/
  - daisyUI Laravel Installation: https://daisyui.com/docs/install/laravel/
  - Mary UI Homepage: https://mary-ui.com/
  - Mary UI Installation: https://mary-ui.com/docs/installation
  - Laravel Livewire Quickstart: https://livewire.laravel.com/docs/4.x/quickstart
  - Laravel Livewire Installation: https://livewire.laravel.com/docs/4.x/installation
  - GitHub Repository daisyUI: https://github.com/saadeghi/daisyui
  - GitHub Repository Mary UI: https://github.com/robsontenorio/mary

---

## ADR-005 Admin-Panel für interne Verwaltungsfunktionen

- **Datum:** 2026-03-19
- **Status:** Accepted
- **Entscheidung:**
  - Einsatz von **Laravel Filament** als Admin-Panel für interne Verwaltungsfunktionen unter `/admin`
  - Einsatzbereich insbesondere für:
    - Benutzerverwaltung
    - Rollen- und Rechteverwaltung (mittels Plugin Filament Shield / Spatie)
    - Standard-CRUD für administrative Daten
    - interne Übersichts- und Pflegeoberflächen
- **Begründung:**
  - Für das Projekt werden interne Adminfunktionen benötigt, z. B. Benutzer bearbeiten, Datensätze verwalten und administrative Standardprozesse umsetzen
  - Das riesige Plugin-Ökosystem (z. B. Filament Shield für eine sofort einsatzbereite Spatie-Rechteverwaltung) reduziert den Entwicklungsaufwand massiv
  - Filament bringt extrem mächtige Kernkomponenten mit (Form Builder, Table Builder), die komplexe Anforderungen (Filter, Relationen, Repeater) out-of-the-box abdecken
  - Filament ist MIT-lizenziert und damit frei nutzbar, auch für spätere kommerzielle Weiterentwicklung
  - Sehr große, aktive Community, was langfristigen Support garantiert
- **Abgrenzung:**
  - Filament wird als Lösung für den **internen Adminbereich** eingesetzt
  - Die eigentliche Portaloberfläche für reguläre Nutzer bleibt davon getrennt und wird nicht auf Laravel Filament aufgebaut
- **Verworfene Alternativen:**
  - MoonShine: Wurde als leichtgewichtige Alternative evaluiert. Die Entscheidung fiel jedoch gegen MoonShine und für Filament, da Filament ein deutlich größeres Plugin-Ökosystem bietet. Zudem ist die Verbreitung von Filament im Laravel-Umfeld spürbar höher. 
  - Vollständige Eigenentwicklung: Wurde verworfen, da der initiale Entwicklungs- und spätere Wartungsaufwand für Standard-Admin-Funktionen (Tabellen, Formulare, Auth) in keinem wirtschaftlichen Verhältnis zum Nutzen steht.
- **Quellen:**
  - Filament offizielle Website und Dokumentation (filamentphp.com)
  - Filament GitHub-Repository

## ADR-006 PDF-Generierung mit Laravel PDF und Gotenberg
- **Datum:** 2026-04-15
- **Status:** Accepted
- **Entscheidung:**
  - Einsatz von **Spatie Laravel PDF** (`spatie/laravel-pdf`) zur PDF-Erzeugung innerhalb der Laravel-Anwendung
  - Einsatz von **Gotenberg** als externer Rendering-Service
  - PDF-Dokumente werden auf Basis von **Blade-Templates (HTML/CSS)** generiert
  - Einsatzbereich: Erstellung der druckfähigen Besucherausweise
- **Begründung:**
  - **Gotenberg** bietet zuverlässiges Chromium-basiertes PDF-Rendering mit modernem CSS-Support
  - Serverseitige Generierung sorgt für reproduzierbare Ergebnisse unabhängig vom Endgerät oder Browser des Nutzers
  - **Spatie Laravel PDF** ist speziell für Laravel entwickelt und integriert sich nahtlos in die bestehende Entwicklung.
  - Es sind keine zusätzlichen Kenntnisse in separaten PDF-Technologien erforderlich
  - Layouts lassen sich mit HTML/CSS einfach pflegen, erweitern und gestalten
  - **Gotenberg** ist Open Source und unter der **MIT License** veröffentlicht
  - **Spatie Laravel PDF** ist ebenfalls unter der **MIT License** veröffentlicht
  
- **Verworfene Alternativen:**
  - **dompdf:** Nur eingeschränkter CSS-Support für komplexere Layouts, für gewünschte Zwecke nicht ausreichend
  - **Direkter Einsatz von Puppeteer:** Ursprünglich in Betracht gezogen. Verworfen, da zusätzlicher Node.js-Stack, eigener Wartungsaufwand und direktere Prozessverwaltung erforderlich gewesen wären und allgemein zu schwergewichtig wäre.
- **Quellen:**
  - Spatie Laravel PDF: https://spatie.be/docs/laravel-pdf/v2/introduction
  - GitHub Repository Spatie Laravel PDF: https://github.com/spatie/laravel-pdf
  - Gotenberg: https://gotenberg.dev/
  - GitHub Repository Gotenberg: https://github.com/gotenberg/gotenberg
