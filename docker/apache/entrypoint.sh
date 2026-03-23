#!/bin/sh

# Ensure directories exist so chown doesn't fail
mkdir -p /var/www/html/backend/storage
mkdir -p /var/www/html/backend/bootstrap/cache

# Set owner to www-data (PHP user in container)
chown -R www-data:www-data /var/www/html/backend/storage /var/www/html/backend/bootstrap/cache

# Start Apache or PHP-FPM
exec "$@"
