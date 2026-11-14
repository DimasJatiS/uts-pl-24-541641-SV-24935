# syntax=docker/dockerfile:1

# ===========================================================================
# Image backend ChainFolio: Laravel 12 yang dilayani FrankenPHP (Alpine)
# ===========================================================================
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1.13.2-php8.5-trixie

# ---------------------------------------------------------------------------
# Tahap 1 - vendor
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS vendor

RUN install-php-extensions zip

COPY --from=composer:2.8.12 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------------------------------------------------------------------------
# Tahap 2 - runtime
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pdo_mysql intl zip opcache pcntl

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# Buat grup dan user non-root 'laravel'
RUN groupadd --gid 1000 laravel \
    && useradd --uid 1000 --gid laravel --home-dir /app --no-create-home \
        --shell /usr/sbin/nologin laravel \
    && mkdir -p /app /config/caddy /data/caddy \
    && chown -R laravel:laravel /app /config /data

WORKDIR /app

# Salin kode & vendor dari stage 1 dengan kepemilikan user laravel
COPY --from=vendor --chown=laravel:laravel /app /app

# Pastikan folder internal Laravel ada dan hak aksesnya valid
RUN mkdir -p /app/storage/framework/cache/data \
             /app/storage/framework/sessions \
             /app/storage/framework/views \
             /app/storage/logs \
             /app/bootstrap/cache \
    && chown -R laravel:laravel /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chmod +x /app/docker/entrypoint.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stdout \
    LOG_LEVEL=info \
    SERVER_NAME=:8000 \
    SERVER_ROOT=/app/public \
    CADDY_GLOBAL_OPTIONS="admin off"

USER laravel

EXPOSE 8000 9180

HEALTHCHECK --interval=10s --timeout=12s --start-period=40s --retries=3 \
    CMD curl -f http://127.0.0.1:8000/api/health || exit 1

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]