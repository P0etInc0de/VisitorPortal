FROM php:8.4-apache

# Apache: URL-Rewrites für Laravel
RUN a2enmod rewrite

# System libs
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl git unzip libzip-dev libicu-dev libonig-dev libxml2-dev libgmp-dev \
  && rm -rf /var/lib/apt/lists/*

# PHP Extensions
RUN docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip intl opcache dom xml xmlwriter gmp

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache vHost für Laravel in /backend/public
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html/backend

#Start Apache
CMD ["apache2-foreground"]
