#!/bin/sh
# Dijalankan setiap container backend start, sebelum FrankenPHP.
# PENTING: file ini harus ber-line-ending LF (diatur di .gitattributes),
# kalau CRLF container akan gagal start dengan error "not found".
set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY belum diisi. Lihat langkah setup di README." >&2
    exit 1
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Menjalankan migrasi database..."
    php artisan migrate --force
    php artisan db:seed --class=CoinSeeder --force
fi

# Cache konfigurasi dibuat saat runtime karena nilai env baru tersedia di sini.
php artisan config:cache
php artisan route:cache
php artisan event:cache

exec "$@"
