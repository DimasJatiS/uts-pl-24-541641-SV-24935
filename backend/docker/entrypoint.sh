#!/bin/sh
set -e   # Berhenti bila ada perintah gagal

cd /app

# 1 - Berkas .env. Copy .env.example jika .env belum ada di folder /app.
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 2 - APP_KEY. Generate jika environment variable APP_KEY belum diset.
if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

# 3 - Basis data. Tunggu MySQL sampai siap menerima koneksi (maksimal 30 x 2 detik).
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    mkdir -p database
    touch database/database.sqlite
fi

# Clear cache lama terlebih dahulu agar tidak mengunci state config corrupt saat startup
php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true

percobaan=1
until php artisan migrate --force; do
    if [ "${percobaan}" -ge 30 ]; then
        echo "Basis data tidak dapat dihubungi, container dihentikan." >&2
        exit 1
    fi
    echo "Menunggu basis data siap (percobaan ${percobaan})..."
    percobaan=$((percobaan + 1))
    sleep 2
done

# 4 - Data awal ChainFolio. Jalankan CoinSeeder secara otomatis.
if php artisan db:seed --class=CoinSeeder --force 2>/dev/null; then
    echo "Seeder CoinSeeder berhasil dijalankan."
else
    # Fallback ke DatabaseSeeder umum jika CoinSeeder tidak dipanggil langsung
    php artisan db:seed --force || true
fi

# 5 - Cache konfigurasi, route, dan view untuk lingkungan produksi.
php artisan optimize

# 6 - Jalankan perintah utama container (CMD dari Dockerfile).
exec "$@"