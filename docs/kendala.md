# Catatan kendala

Isi setiap kali menemukan masalah. Bagian ini langsung dipakai untuk laporan
(poin "kendala yang ditemui dan cara mengatasinya").

| Tanggal | Soal | Kendala | Penyebab | Solusi |
| --- | --- | --- | --- | --- |
| 2026-10-23 | 1, 5 | Requirement dinaikkan ke `"php": "^8.5"`, tetapi Composer/test tidak bisa dijalankan di laptop | PHP lokal (Laragon) masih 8.2 | `composer update`, `php artisan test`, dan Pint dijalankan di dalam container `dunglas/frankenphp:1.13.2-php8.5-trixie`, jadi versi PHP sama dengan image produksi (PHP 8.5.12) |
| 2026-10-23 | 5a | `backend/Dockerfile` menyalin `composer.lock`, tetapi file itu belum ada di repository | Lock file belum pernah dibuat | Membuat `composer.lock` dengan PHP 8.5 lalu meng-commit-nya; lock juga dipakai sebagai kunci cache Composer di CI |
| 2026-10-23 | 1 | `composer update` lewat bind-mount folder Windows berjalan sangat lambat (lebih dari 10 menit, akhirnya dihentikan) | I/O bind-mount Docker Desktop ke folder Windows lambat untuk ribuan file `vendor/` | Source disalin ke dalam container memakai `tar` (tanpa `vendor/`), install dan test berjalan di filesystem container (selesai sekitar 1 menit) |
| 2026-10-23 | 6 | Port 8080 di laptop sudah dipakai container nginx dari project lain (`docker ps`) | Bentrok port host | Port host diatur lewat `FRONTEND_PORT` di `.env` (diisi `8081`, beserta `APP_URL=http://localhost:8081`), tanpa mengubah `docker-compose.yml` |
