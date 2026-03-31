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

## Quellen

- Laravel Scheduler: https://laravel.com/docs/12.x/scheduling#introduction