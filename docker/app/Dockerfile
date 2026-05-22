# syntax=docker/dockerfile:1

FROM php:8.4-apache AS php-base

RUN a2enmod rewrite headers \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libgmp-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        dom \
        gmp \
        intl \
        mbstring \
        opcache \
        pdo_mysql \
        xml \
        xmlwriter \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app/php.ini /usr/local/etc/php/conf.d/besucherportal.ini

WORKDIR /var/www/html/backend

FROM php-base AS vendor

COPY backend/composer.json backend/composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY backend ./
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi

FROM node:24-alpine AS assets

WORKDIR /app/backend
COPY backend/package.json backend/package-lock.json ./
# Avoid a BuildKit/npm race where esbuild validates its binary before unpacking is fully settled.
RUN npm ci --no-audit --no-fund --ignore-scripts \
    && npm rebuild esbuild --foreground-scripts

COPY --from=vendor /var/www/html/backend/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY backend/resources ./resources
COPY backend/public ./public
COPY backend/vite.config.js ./vite.config.js
RUN npm run build \
    && npm cache clean --force

FROM php-base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_SETUP=true \
    AUTO_MIGRATE=false \
    AUTO_SEED=false \
    FORCE_SEED=false

COPY backend ./
COPY --from=vendor /var/www/html/backend/vendor ./vendor
COPY --from=vendor /var/www/html/backend/bootstrap/cache ./bootstrap/cache
COPY --from=assets /app/backend/public/build ./public/build
COPY docker/app/entrypoint.sh /usr/local/bin/besucherportal-entrypoint

RUN chmod +x /usr/local/bin/besucherportal-entrypoint \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["besucherportal-entrypoint"]
CMD ["apache2-foreground"]

FROM app AS sso-test

ENV APP_ENV=testing \
    APP_DEBUG=false \
    RUN_SETUP=false \
    AUTO_MIGRATE=false \
    AUTO_SEED=false \
    FORCE_SEED=false

RUN test -f vendor/autoload.php \
    && test -f public/build/manifest.json
