# Persekitaran Dev (Windows) — Nadi Qurban OSI

| Alat | Versi | Lokasi |
|---|---|---|
| PHP (NTS) | 8.4.26 | `C:\php84` (dalam PATH pengguna), `php.ini` dengan openssl, pdo_mysql, mbstring, intl, gd, zip, exif, sodium, sockets |
| Composer | 2.10 | `C:\php84\composer\composer.bat` |
| Node.js | 24 LTS | `C:\Program Files\nodejs` |
| MySQL | 8.4.9 | binari `C:\Program Files\MySQL\MySQL Server 8.4`, data `C:\mysql84\data`, **port 3307** |

XAMPP (PHP 8.2 + MariaDB pada 3306) tidak diubah. Projek ini guna MySQL 8.4 pada **3307**.

## Mula kerja
1. Hidupkan MySQL: `scripts\mysql-start.cmd` (tetingkap diminimumkan; tutup untuk henti).
2. `composer run dev` — serve + queue + vite. Atau `php artisan serve` + `npm run dev`.
3. Buka `http://127.0.0.1:8000` → `/dashboard`. Semakan komponen: `/_design/components`, `/_design/auth`, `/_design/public` (local sahaja).

## Pangkalan data
- `nadiqurban_osi` (app) dan `nadiqurban_osi_test`, pengguna `nq` (kata laluan dalam `.env`, tidak di-commit).
- Ujian Pest guna SQLite in-memory (`phpunit.xml`).

## Ujian & kualiti
```
php artisan test          # unit + feature + browser (Playwright chromium)
vendor/bin/pint --test
vendor/bin/phpstan analyse
```
Pertama kali pada mesin baharu: `npx playwright install chromium`.

## Logo
`node scripts/optimize-logos.mjs` menjana `public/images/*` daripada fail asal dalam `design/` (fail asal besar tidak di-commit).
