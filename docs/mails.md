# Mails

Für den Mailversand nutzen wir das **SMTP-Protokoll**. Das Unternehmen benötigt hierfür lediglich ein dediziertes E-Mail-Konto.

## Mailhog

MailHog ist ein E-Mail-Testing-Tool für Entwickler.

- Es wird in einem separaten Docker-Container installiert und via `docker-compose up` gestartet.
- Es nutzt ebenfalls das SMTP-Protokoll.
- Die grafische Benutzeroberfläche (**GUI**) ist erreichbar unter: `http://0.0.0.0:8025/`
## Laravel-Mail Konfiguration

- In der `.env`-Konfigurationsdatei können wir Laravel anweisen, SMTP zu nutzen und sich mit MailHog zu verbinden.
    - Dies kann später problemlos auf ein echtes Konto und einen realen SMTP-Server umgestellt werden.
- Weitere spezifische Konfigurationen können in der Datei `config/mail.php` vorgenommen werden.

## Erstellen von "Mailables"

Jeder E-Mail-Typ wird durch eine sogenannte **"Mailable"**-Klasse repräsentiert.

- Diese Klassen werden im Verzeichnis `app/Mail` gespeichert.
- Erstellung via: `php artisan make:mail TestMail`

Innerhalb der Datei `TestMail.php` kann der Inhalt als **Blade-View**, **Markdown** oder **Klartext** definiert werden. Um ein Mailable direkt mit einem passenden Markdown-Template zu generieren, nutzt man die `--markdown` Option:

```bash
php artisan make:mail OrderShipped --markdown=mail.orders.shipped
```

- Markdown-Mailables verwenden eine Kombination aus Blade-Komponenten und Markdown-Syntax. Dies ermöglicht es, E-Mails einfach zu erstellen und dabei die vorgefertigten UI-Komponenten von Laravel zu nutzen.

## E-Mails versenden

Innerhalb eines Controllers oder einer PHP-Klasse können wir einfach folgenden Befehl nutzen:

```php
 Mail::to($request->user())->send(new Mailable());
```

Standardmäßig verwendet Laravel den Mailer, der in der Konfigurationsdatei als Standard (Default) hinterlegt ist (in unserem Fall **SMTP**). Eine weitere (und oft bessere) Option ist die Verwendung von **Laravel Notifications**.

### Queuing (see [[Laravel Queues]])

_(Siehe auch Abschnitt: Laravel Queues)_
Um die E-Mail asynchron im Hintergrund zu versenden, nutzt man:

```php
 Mail::to($request->user())->queue(new Mailable());
```

## Quellen

- Mail: https://laravel.com/docs/13.x/mail
- Mailhog: https://github.com/mailhog/MailHog/tree/master