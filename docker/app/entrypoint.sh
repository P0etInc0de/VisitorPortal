#!/usr/bin/env sh
set -eu

APP_DIR="/var/www/html/backend"
INSTALL_FLAG="$APP_DIR/storage/app/.besucherportal-installed"
STEP=0

step() {
    STEP=$((STEP + 1))
    printf '\n[STEP %s] %s\n' "$STEP" "$1"
}

info() {
    printf '[INFO] %s\n' "$1"
}

ensure_writable_paths() {
    mkdir -p \
        "$APP_DIR/storage/app/public" \
        "$APP_DIR/storage/framework/cache" \
        "$APP_DIR/storage/framework/sessions" \
        "$APP_DIR/storage/framework/views" \
        "$APP_DIR/storage/logs" \
        "$APP_DIR/bootstrap/cache"

    if [ "$(id -u)" = "0" ]; then
        chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
    fi

    chmod -R ug+rwX "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
}

wait_for_database() {
    if [ "${DB_CONNECTION:-mysql}" != "mysql" ]; then
        info "DB_CONNECTION is '${DB_CONNECTION:-unset}', skipping MySQL wait."
        return 0
    fi

    DB_HOST_VALUE="${DB_HOST:-db}"
    DB_PORT_VALUE="${DB_PORT:-3306}"
    export DB_HOST_VALUE DB_PORT_VALUE

    php -r '
        $host = getenv("DB_HOST_VALUE") ?: "db";
        $port = (int) (getenv("DB_PORT_VALUE") ?: 3306);
        $maxAttempts = 60;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $socket = @fsockopen($host, $port, $errno, $errstr, 2);

            if ($socket) {
                fclose($socket);
                fwrite(STDOUT, "[INFO] Database is reachable at {$host}:{$port}.\n");
                exit(0);
            }

            fwrite(STDOUT, "[INFO] Waiting for database ({$attempt}/{$maxAttempts})...\n");
            sleep(2);
        }

        fwrite(STDERR, "[ERROR] Database did not become reachable.\n");
        exit(1);
    '
}

ensure_application_key() {
    if [ -n "${APP_KEY:-}" ]; then
        info "APP_KEY provided by environment."
        return 0
    fi

    if [ ! -f "$APP_DIR/.env" ]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
    fi

    if grep -q '^APP_KEY=base64:' "$APP_DIR/.env"; then
        info "APP_KEY already present in .env."
        return 0
    fi

    php artisan key:generate --force
}

cd "$APP_DIR"

step "Preparing writable Laravel directories"
ensure_writable_paths

step "Checking application key"
ensure_application_key

step "Checking database availability"
wait_for_database

if [ "${RUN_SETUP:-true}" = "true" ]; then
    step "Running database migrations"
    if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
        php artisan migrate --force
    else
        info "AUTO_MIGRATE=false, skipping migrations."
    fi

    step "Loading demo data if needed"
    if [ "${AUTO_SEED:-true}" = "true" ]; then
        if [ "${FORCE_SEED:-false}" = "true" ] || [ ! -f "$INSTALL_FLAG" ]; then
            php artisan db:seed --force
            touch "$INSTALL_FLAG"
        else
            info "Demo data already loaded. Set FORCE_SEED=true to seed again."
        fi
    else
        info "AUTO_SEED=false, skipping seeders."
    fi
else
    step "Skipping setup tasks"
    info "RUN_SETUP=false, not running migrations or seeders."
fi

step "Finalizing Laravel runtime"
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true
ensure_writable_paths

info "Starting: $*"
exec "$@"
