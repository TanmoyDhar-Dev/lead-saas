#!/bin/sh
set -eu

cd /var/www/html

log() {
    printf '[entrypoint] %s\n' "$1"
}

wait_for_database() {
    connection="${DB_CONNECTION:-pgsql}"

    if [ "$connection" = "sqlite" ]; then
        return 0
    fi

    host="${DB_HOST:-127.0.0.1}"
    port="${DB_PORT:-5432}"
    max_attempts="${DB_WAIT_ATTEMPTS:-60}"
    attempt=1

    log "Waiting for database at ${host}:${port} (${connection})..."

    while [ "$attempt" -le "$max_attempts" ]; do
        if php -r '
            $connection = getenv("DB_CONNECTION") ?: "pgsql";
            $host = getenv("DB_HOST") ?: "127.0.0.1";
            $port = getenv("DB_PORT") ?: ($connection === "pgsql" ? "5432" : "3306");
            $database = getenv("DB_DATABASE") ?: "";
            $username = getenv("DB_USERNAME") ?: "";
            $password = getenv("DB_PASSWORD") ?: "";
            $sslmode = getenv("DB_SSLMODE") ?: "prefer";

            try {
                if ($connection === "pgsql") {
                    $dsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
                        $host,
                        $port,
                        $database,
                        $sslmode
                    );
                } else {
                    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", $host, $port, $database);
                }

                new PDO($dsn, $username, $password);
                exit(0);
            } catch (Throwable) {
                exit(1);
            }
        '; then
            log "Database is ready."
            return 0
        fi

        attempt=$((attempt + 1))
        sleep 2
    done

    log "Database did not become ready in time."
    exit 1
}

prepare_runtime() {
    mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwx storage bootstrap/cache
}

run_migrations() {
    if [ "${RUN_MIGRATIONS:-false}" != "true" ]; then
        return 0
    fi

    log "Running database migrations..."
    php artisan config:clear --no-interaction
    php artisan migrate --force --no-interaction

    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        log "Seeding database..."
        php artisan db:seed --force --no-interaction
    fi
}

run_app_bootstrap() {
    if [ "${CONTAINER_ROLE:-app}" != "app" ]; then
        return 0
    fi

    if [ ! -L public/storage ]; then
        log "Creating public storage symlink..."
        php artisan storage:link --force
    fi

    if [ "${APP_ENV:-production}" = "production" ]; then
        log "Caching configuration for production..."
        php artisan config:cache --no-interaction
        php artisan route:cache --no-interaction
        php artisan view:cache --no-interaction
    fi
}

if [ "$(id -u)" = "0" ]; then
    prepare_runtime
    wait_for_database
    run_migrations
    run_app_bootstrap

    if [ "$#" -eq 0 ]; then
        set -- php-fpm
    fi

    exec "$@"
fi

wait_for_database

if [ "$#" -eq 0 ]; then
    set -- php-fpm
fi

exec "$@"
