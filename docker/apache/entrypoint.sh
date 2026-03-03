#!/bin/sh

# Set owner to www-data (PHP user in container)
chown -R www-data:www-data /var/www/html/backend/storage /var/www/html/backend/bootstrap/cache

# Start Apache or PHP-FPM
exec "$@"
