# Nadi Qurban OSI

[![CI](https://github.com/nadiqurban/Nadi-Qurban-OSI-/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/nadiqurban/Nadi-Qurban-OSI-/actions/workflows/ci.yml)

Sistem operasi dalaman Nadi Qurban Sdn. Bhd. — Qurban, Aqiqah, Dam & Nazar Haiwan: tempahan & bayaran, lafaz akad, agihan negara & vendor, pelaksanaan & laporan, AWB & sijil, bayaran ansuran (portal awam), kewangan, CRM, dokumen, laporan, audit log, notifikasi, API & webhook.

**Stack:** Laravel 13 · PHP 8.4 · MySQL 8.4 · Livewire 4 + Alpine · Tailwind CSS v4 · Pest 4 (unit, feature, browser) · Larastan · Sanctum · spatie/laravel-pdf (Browsershot).

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | Konvensyen projek, token reka bentuk, peraturan responsif |
| [`docs/PRD.md`](docs/PRD.md) | Keperluan produk |
| [`docs/SETUP-DEV.md`](docs/SETUP-DEV.md) | Persekitaran pembangunan (Windows) |
| [`docs/DEPLOY.md`](docs/DEPLOY.md) | Deploy GitHub → Laravel Forge |
| [`docs/api.md`](docs/api.md) | API v1 & webhook keluar (OpenAPI) |
| [`docs/security-audit.md`](docs/security-audit.md) | Audit keselamatan Fasa 10 |

## Mula cepat (local)

```bash
composer install && npm ci
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
composer run dev
```

## Ujian

```bash
vendor/bin/pest tests/Unit tests/Feature
vendor/bin/pest tests/Browser            # perlu `npm run build` + Playwright
vendor/bin/pint --test && vendor/bin/phpstan
```

## Cawangan

`develop` untuk kerja harian → CI hijau → merge ke `main` → Forge auto-deploy.
