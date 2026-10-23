# syntax=docker/dockerfile:1
#
# Backend ChainFolio: Laravel di atas FrankenPHP (nilai tambahan soal 7).
#
# Multi-stage:
#   1. base    → image FrankenPHP + ekstensi PHP yang dibutuhkan
#   2. vendor  → composer install (tanpa dev dependency)
#   3. runtime → hanya kode + vendor, berjalan sebagai user non-root
#
# Catatan keamanan: TIDAK ada nilai rahasia (APP_KEY, password DB, API key) di file
# ini. Semuanya diberikan lewat environment saat container dijalankan.

# Tag versi eksplisit (bukan "latest"): FrankenPHP 1.13.2, PHP 8.5, Debian trixie.
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1.13.2-php8.5-trixie

# ── 1. base ──────────────────────────────────────────────────────────────────
FROM ${FRANKENPHP_IMAGE} AS base

# Ekstensi berubah jarang → layer ini hampir selalu diambil dari cache.
RUN install-php-extensions pdo_mysql intl zip opcache pcntl

WORKDIR /app

# ── 2. vendor ────────────────────────────────────────────────────────────────
FROM base AS vendor

COPY --from=composer:2.10 /usr/bin/composer /usr/bin/composer

# Salin composer.json/lock DULU supaya layer `composer install` tetap ter-cache
# selama dependency tidak berubah (mengubah kode aplikasi tidak memicu install ulang).
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

COPY . .
# dump-autoload juga menjalankan `artisan package:discover` (script post-autoload-dump).
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ── 3. runtime ───────────────────────────────────────────────────────────────
FROM base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

# Konfigurasi PHP production (opcache, tanpa display_errors).
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-chainfolio.ini"
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/chainfolio-entrypoint

ARG USER=appuser
ARG UID=10001
# User non-root. Port 8000 tidak butuh capability khusus, jadi capability
# bawaan binary frankenphp (untuk port 80/443) dicabut.
RUN useradd --uid ${UID} --create-home --shell /usr/sbin/nologin ${USER} \
    && setcap -r /usr/local/bin/frankenphp \
    && mkdir -p /data/caddy /config/caddy \
    && chown -R ${USER}:${USER} /data/caddy /config/caddy

COPY --from=vendor --chown=${USER}:${USER} /app /app

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R ${USER}:${USER} storage bootstrap/cache

USER ${USER}

EXPOSE 8000

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=5 \
    CMD curl -fsS http://127.0.0.1:8000/api/health || exit 1

ENTRYPOINT ["chainfolio-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
