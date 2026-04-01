# Notifications

Laravel notifications sind einfache PHP Klassen, welche Benachrichtigungen über verschiedene Kanäle anstoßen können ([[Laravel Mails]], Teams, ...).

### Wir benötigen folgende Benachrichtigungen:

1. Reminder am Besuchstag (Uhrzeit fix, z.B. 07:00) 
2. Reminder zusätzlich 1h vorher (Stretch) 
3. “Gast ist eingetroffen” direkt nach Check-in
4. Besuch erstellt

### Dafür nutzen wir folgende notifications

- Guest
  - VisitCreated
  - SingleVisitReminder
- Host
  - VisitCreated
  - SingleVisitReminder
  - VisitReminderDaily
  - GuestCheckedIn

### Erstelle eine neue notification:

```bash
artisan make:notification
```

### Senden von Benachrichtigungen

```php
Notification::send($request->user(), new TestNotification());
```

## Quellen

- Notification: https://laravel.com/docs/13.x/notifications
