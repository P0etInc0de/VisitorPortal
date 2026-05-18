# Queues

## Queuing

- Die Queue ist erforderlich fuer Benachrichtigungen und Hintergrundjobs.
- Das direkte Versenden einer Benachrichtigung oder E-Mail aus PHP heraus kann die Antwortzeit der Anwendung verschlechtern.
- Wir nutzen Queues und einen Worker, um den Versandprozess im Hintergrund auszufuehren.

Eine Benachrichtigung kann gesendet werden, indem sie das Interface `ShouldQueue` implementiert:

```php
Notification::send($request->user(), new VisitReminderDaily());
```

## Worker

Empfohlener Worker-Befehl:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600 --max-jobs=1000 --memory=128
```

`DB_QUEUE_RETRY_AFTER` muss groesser als `--timeout` sein. Der Docker-Default ist:

```env
DB_QUEUE_RETRY_AFTER=180
```

## Konfiguration

- Wir verwenden die Datenbank-Queue von Laravel.
- In `config/queue.php` kann die Konfiguration angepasst werden.
- Die Queue nutzt die Datenbanktabelle `jobs`.
- Fehlgeschlagene Jobs werden in `failed_jobs` gespeichert.
- Alle erforderlichen Datenbanktabellen sind bereits vorhanden.

## Betrieb

Failed Jobs pruefen:

```bash
php artisan queue:failed
```

Logs pruefen:

```bash
docker compose logs -f queue
```

Health pruefen:

```bash
php artisan visitorportal:health queue
```

## Quellen

- Laravel Queues: https://laravel.com/docs/12.x/queues#main-content
