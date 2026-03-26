# Queues

### Queuing

- Das direkte Versenden einer Benachrichtigung oder E-Mail aus PHP heraus kann die Antwortzeit (**Responsiveness**) unserer Anwendung verschlechtern.
- Wir nutzen **Queues** und einen **Worker**, um den Versandprozess im Hintergrund auszuführen.
- Um unser Projekt übersichtlich zu halten, fügen wir unserer `docker-compose`-Datei einen neuen Container hinzu. Dieser startet einen Worker mit folgendem Befehl:

```bash 
php artisan queue:work
```

- Eine Benachrichtigung kann anschließend wie folgt gesendet werden:
    - Sie muss lediglich das Interface `ShouldQueue` implementieren.

```php
 Notification::send($request->user(), new VisitReminderDaily());
```

### Konfiguration

- Wir verwenden die Standardkonfiguration von Laravel.
- In der datei `config/queue.php` kann die Konfiguration angepasst werden
- Die Queue ist lediglich eine Datenbanktabelle namens **"Jobs"**.
- Alle erforderlichen Datenbanktabellen sind bereits vorhanden.

## Quellen

- Laravel Queues: https://laravel.com/docs/12.x/queues#main-content