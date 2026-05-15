# Scheduler

## Einrichten von Remindern

Manche Benachrichtigungen müssen zu bestimmten Uhrzeiten angestoßen werden. Dafür können wir den Scheduler von Laravel nutzen.

- Der Befehl `schedule:work` kann in einem separaten Docker Container ausgeführt werden.
- Dadurch werden automatisch die definierten Tasks zur richtigen Uhrzeit ausgeführt.
    - Tasks werden in `routes/console.php` mit der "Scheduler-Facade" aufgerufen.
### Definieren von Schedulern

- Zunächst definieren wir ein invokable-Objekt
    - Invokable-Objekte sind einfache PHP Klassen mit einer `__invoke()` Methode.
    - Diese Klassen kommen in `app/Tasks`
    - Darin kann dann die Datenbank abgefragt werden und entsprechende Notifications können versendet werden.
- Dann können wir das Objekt mittels der "Scheduler-Facade" aufrufen.
- Der Aufruf erfolgt in `routes/console.php`

```PHP
Schedule::call(new DailyVisitorReminder())->daily();
```

## Manage Scheduler

- `php artisan schedule:list`
  - Auflisten der Scheduler
- `php artisan schedule:test`
  - Ausführen eines Schedulers

## Retention

- `visits:purge-expired` bereinigt abgelaufene Besuche nach der konfigurierten Frist.
- `php artisan visits:purge-expired --dry-run` zählt betroffene Datensätze ohne Löschung.
- Der Scheduler führt den Command täglich aus, wenn `PRIVACY_PURGE_ENABLED=true` gesetzt ist.
- Der Command bereinigt zusätzlich alte Notifications, verwaiste Visitors, alte auto-generierte Monitor-Slides und alte manuelle Monitor-Besuchernamen.
- Referenzen auf gelöschte Visitors werden aus Monitor-Slides entfernt.
- Ein `retention_hold_reason` ohne `retention_hold_until` gilt als unbefristeter Legal Hold und wird im Retention-Run gezählt.

## Quellen

- Laravel Scheduler: https://laravel.com/docs/12.x/scheduling#introduction
