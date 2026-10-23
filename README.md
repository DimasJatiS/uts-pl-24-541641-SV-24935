# ChainFolio

Crypto portfolio tracker: catat koin yang kamu pegang, lalu lihat nilai portofolio dan untung/rugi berdasarkan harga dari CoinGecko.

- **Backend:** Laravel 13 REST API, login token Laravel Sanctum, berjalan di **FrankenPHP**
- **Frontend:** Vue 3 + Vue Router + Axios (Vite)
- **Database:** MySQL 8.4
- **Infrastruktur:** Docker multi-stage, Docker Compose, GitHub Actions → GitHub Container Registry

```
browser ──:8080──▶ frontend (nginx) ──/api──▶ backend (FrankenPHP + Laravel) ──▶ db (MySQL)
                                                     └──▶ CoinGecko (harga, di-cache 60 detik)
```

## Menjalankan dengan Docker Compose

Prasyarat: Docker Desktop (atau Docker Engine + Compose v2).

```bash
cp .env.example .env
# Isi APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD di .env. Contoh membuat APP_KEY:
#   echo "base64:$(openssl rand -base64 32)"
docker compose up -d --build
docker compose ps
```

Buka http://localhost:8080, daftar akun, lalu tambahkan holding.

Perintah berguna:

| Perintah | Fungsi |
| --- | --- |
| `docker compose logs -f backend` | Melihat log Laravel/FrankenPHP |
| `docker compose exec backend php artisan migrate:status` | Status migrasi |
| `docker compose down` | Mematikan container (data DB tetap ada di volume `db-data`) |
| `docker compose down -v` | Mematikan container **dan menghapus data DB** |

## Development tanpa Docker

Prasyarat: PHP 8.5 dengan ekstensi `pdo_mysql`, Composer 2, Node.js 22, MySQL.

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
# buat database "chainfolio" di MySQL, sesuaikan DB_* di .env
php artisan migrate --seed      # termasuk akun demo: demo@chainfolio.test / Demo12345
php artisan serve               # http://localhost:8000

# Frontend (terminal lain)
cd frontend
cp .env.example .env
npm install
npm run dev                     # http://localhost:5173 (proxy /api → :8000)
```

## Test

```bash
cd backend && php artisan test     # feature + unit test (SQLite in-memory di lokal, MySQL di CI)
cd frontend && npm run lint && npm run build
```

Postman Collection ada di [`docs/postman/`](docs/postman/ChainFolio.postman_collection.json). Jalankan berurutan dengan Collection Runner.

## Endpoint API

| Method | Path | Auth | Status |
| --- | --- | --- | --- |
| GET | `/api/health` | – | 200, 503 |
| POST | `/api/register` | – | 201, 422, 429 |
| POST | `/api/login` | – | 200, 401, 422, 429 |
| GET | `/api/me` | Bearer | 200, 401 |
| POST | `/api/logout` | Bearer | 200, 401 |
| GET | `/api/coins` | Bearer | 200, 401 |
| GET | `/api/holdings` | Bearer | 200, 401 |
| POST | `/api/holdings` | Bearer | 201, 401, 422 |
| GET | `/api/holdings/{id}` | Bearer | 200, 401, 404 |
| PUT | `/api/holdings/{id}` | Bearer | 200, 401, 404, 422 |
| DELETE | `/api/holdings/{id}` | Bearer | 200, 401, 404 |
| GET | `/api/portfolio/summary` | Bearer | 200, 401 |

## Git workflow

- `main` → kode yang siap rilis, setiap push memicu deploy (build & push image ke ghcr.io).
- `dev` → integrasi. Semua fitur masuk ke sini lewat Pull Request.
- `feat/*`, `fix/*`, `ci/*`, `docs/*` → branch kerja.
- Pesan commit mengikuti [Conventional Commits](https://www.conventionalcommits.org/): `feat(api): tambah endpoint holdings`.
- `main` dan `dev` dilindungi: wajib Pull Request dan status check `backend` + `frontend` lulus.

## CI/CD (`.github/workflows/ci.yml`)

| Job | Kapan | Isi |
| --- | --- | --- |
| `backend` | PR & push ke `dev`/`main` | composer validate & audit, Pint, `php artisan test` di MySQL |
| `frontend` | PR & push ke `dev`/`main` | `npm ci`, ESLint, `npm audit`, `npm run build` |
| `deploy` | hanya push ke `main`, setelah dua job di atas lulus | build & push image ke ghcr.io, lalu smoke test dengan docker compose |

Secrets (Settings → Secrets and variables → Actions → Repository secrets): `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, dan opsional `COINGECKO_API_KEY`.

## Keamanan yang diterapkan

- Token Sanctum berprefix, kedaluwarsa 24 jam, dicabut saat logout; rate limit login 5x/menit.
- Respons login gagal identik untuk email salah maupun password salah.
- Data milik user lain dibalas 404; validasi FormRequest; `$fillable` eksplisit; API Resource tanpa field sensitif.
- Uang dan jumlah kripto disimpan sebagai `DECIMAL` dan dihitung dengan `brick/math`, bukan float.
- API key CoinGecko hanya di backend dan dikirim lewat header.
- nginx: CSP ketat, `X-Frame-Options`, `nosniff`; ESLint melarang `v-html`.
- Container non-root, `cap_drop: ALL`, `no-new-privileges`, port database tidak dibuka, jaringan DB `internal`.
- Tidak ada rahasia di Dockerfile maupun repository; `.env` di-ignore oleh git dan `.dockerignore`.
