# Deploy — GitHub → Laravel Forge

Sasaran: `https://osi.nadiqurban.com` · repo `nadiqurban/Nadi-Qurban-OSI-` · branch **`main`** (auto-deploy).
Aliran kerja: semua kerja di `develop` → CI hijau → merge `develop` ke `main` → Forge *Quick Deploy*.

> **Tiada rahsia dalam repo.** Semua nilai sulit dimasukkan terus dalam Forge › Site › *Environment*.

---

## 1. Pelayan (Forge › Create Server)

| Tetapan | Nilai |
|---|---|
| Provider | DigitalOcean / Hetzner / AWS (min. 2 vCPU, 4 GB RAM — Chrome untuk PDF) |
| Type | App Server |
| PHP | **8.4** |
| Database | **MySQL 8.4** |
| Node | LTS (Forge › Server › *Node* atau `nvm`) |
| Region | Singapura (paling hampir dengan Malaysia) |

Selepas pelayan siap:

1. **Database** › *Create*: nama `nadiqurban_osi`, user `nq` dengan kata laluan kuat (simpan dalam pengurus kata laluan).
2. **PHP** › pastikan extension `intl`, `gd`, `zip`, `bcmath`, `exif`, `sodium` aktif (lalai Forge).
3. **Timezone** pelayan: `Asia/Kuala_Lumpur` (Forge › Server › *Settings*).

### 1.1 Chromium untuk PDF (spatie/laravel-pdf + Browsershot)

Resit, PO, AWB, waybill, invois, sijil dan laporan PDF dijana dengan headless Chrome (`chrome-headless-shell` versi puppeteer dalam `package.json`). Skrip deploy (§4) memasangnya ke `~/.cache/puppeteer` milik user `forge`. Library sistem dipasang sekali sahaja melalui Forge › **Recipes** (Runs as `root`):

```bash
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
npx --yes playwright@1.63.0 install-deps chromium
apt-get install -y -q fonts-noto-core fonts-liberation
```

Semak lokasi Node/npm untuk env di bawah:

```bash
which node   # cth. /usr/bin/node
which npm    # cth. /usr/bin/npm
```

Uji selepas deploy pertama: `php artisan tinker --execute "Spatie\LaravelPdf\Facades\Pdf::html('<h1>OK</h1>')->save(storage_path('app/ujian.pdf'));"` — fail mesti wujud.

---

## 2. Site (Forge › Server › *New Site*)

| Tetapan | Nilai |
|---|---|
| Root domain | `osi.nadiqurban.com` |
| Project type | Laravel |
| Web directory | `/public` |
| PHP | 8.4 |
| Repository | `nadiqurban/Nadi-Qurban-OSI-` (sambung GitHub dalam Forge › Account › *Source Control*) |
| Branch | **`main`** |
| Composer | ✔ install |
| Quick Deploy | **On** |

Kemudian:

- **SSL** › *Let's Encrypt* untuk `osi.nadiqurban.com` (DNS A-record mesti sudah menghala ke IP pelayan).
- **Security** › biarkan nginx Forge lalai (dotfiles disekat, tiada autoindex).

---

## 3. Environment (Forge › Site › *Environment*)

Isi nilai sebenar di Forge sahaja. Senarai lengkap:

```dotenv
APP_NAME="Nadi Qurban OSI"
APP_ENV=production
APP_KEY=                       # Forge jana; atau: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://osi.nadiqurban.com
APP_LOCALE=ms
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ms_MY
APP_TIMEZONE=Asia/Kuala_Lumpur

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nadiqurban_osi
DB_USERNAME=nq
DB_PASSWORD=                   # dari langkah 1

SESSION_DRIVER=database
SESSION_LIFETIME=30            # sesi tamat 30 minit tidak aktif (PRD)
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=330      # mesti > --timeout worker (300)
FILESYSTEM_DISK=local          # lihat §8 Storan fail

MAIL_MAILER=smtp
MAIL_HOST=                     # cth. smtp.mailgun.org / Amazon SES
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=noreply@nadiqurban.com
MAIL_FROM_NAME="Nadi Qurban"

# Super Admin pertama (dicipta oleh `db:seed`, terus masuk portal selepas log masuk)
SUPERADMIN_NAME="Nama Pentadbir"
SUPERADMIN_EMAIL=
SUPERADMIN_PASSWORD=

# PDF (Browsershot)
LARAVEL_PDF_NODE_BINARY=/usr/bin/node
LARAVEL_PDF_NPM_BINARY=/usr/bin/npm
LARAVEL_PDF_NO_SANDBOX=true
LARAVEL_PDF_CHROME_PATH=       # kosong = Chrome Puppeteer dalam ~/.cache/puppeteer

# CHIP Collect (portal ansuran). Kunci juga boleh diisi di Tetapan › Integrasi API (disimpan tersulit).
CHIP_FAKE=false
CHIP_BRAND_ID=
CHIP_SECRET_KEY=

# WhatsApp (pilihan) — gateway yang menerima POST {to, message}
WHATSAPP_API_URL=
WHATSAPP_API_TOKEN=
```

> `CHIP_FAKE` **mesti `false`** dalam produksi (gateway simulasi juga tidak aktif automatik bila `APP_ENV=production`).

---

## 4. Deploy script (Forge › Site › *Deploy Script*)

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npx puppeteer browsers install chrome-headless-shell
npm run build

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan queue:restart
```

**Deploy pertama sahaja** (Forge › Site › *Commands*):

```bash
php artisan db:seed --force
```

Dalam produksi seeder hanya memasukkan peranan & matriks kebenaran, tetapan lalai, negara, pakej dan Super Admin dari env — **tiada data demo**.

---

## 5. Queue worker & scheduler

- **Daemon** (Forge › Server › *Daemons* atau Site › *Queue*):

  ```
  php artisan queue:work --sleep=3 --tries=3 --timeout=300
  ```
  Directory: `/home/forge/osi.nadiqurban.com` · User `forge` · Processes `2`.
  Digunakan oleh: Pusat Laporan, webhook keluar, emel/WhatsApp notifikasi.

- **Scheduler** (Forge › Server › *Scheduler*): `php /home/forge/osi.nadiqurban.com/artisan schedule:run` — **Every Minute**.
  Jadual yang berjalan: `installments:daily` 08:00, `finance:daily` 00:15, `model:prune` 02:30, `activitylog:clean` setiap 1hb 03:00.

---

## 6. Fasa 12 — Ejen & Tempahan Awam

- Halaman awam: `/tempah` (tempahan terus) dan `/tempah/{nama-ejen}` (link ejen; link lama `/e/...` redirect). Bayaran FPX melalui CHIP — pastikan kunci CHIP diisi & `CHIP_FAKE=false`; tanpa CHIP, pelanggan masih boleh pilih Pindahan Bank / Cek (bukti → Pengesahan Bayaran).
- Portal ejen: `/ejen` (log masuk) → `/ejen/portal`. Ejen didaftarkan di **Pengurusan Ejen** (staf).
- Peranan **Ejen** & kebenaran `agents.*` ditambah automatik oleh migration semasa deploy (matriks sedia ada tidak ditimpa).
- Komisen = komisen produk (RM/unit, halaman Produk) × kuantiti, dikira selepas bayaran disahkan.

## 7. Integrasi selepas live

1. **CHIP Collect** — dalam dashboard CHIP daftar webhook:
   `https://osi.nadiqurban.com/webhooks/chip` untuk acara `purchase.paid` dan `purchase.payment_failure`. Salin *public key* webhook ke Tetapan › Integrasi API › CHIP IN.
2. **Webhook keluar / API** — urus di Tetapan › Webhooks dan Tetapan › Integrasi API (rujukan: `docs/api.md`).
3. **Tetapan › Maklumat Syarikat** — semak nama, SSM, bank, tahun musim & sasaran jualan.

---

## 8. Storan fail

Semua fail sulit (bukti bayaran, resit vendor, media pelaksanaan, dokumen, laporan) disimpan pada disk `local` (`storage/app/private`) dan dihidang melalui URL bertandatangan + semakan kebenaran. Untuk satu pelayan Forge ini paling ringkas; pastikan **backup** (§9) merangkumi folder `storage/app`.

> DigitalOcean Spaces / S3 belum diaktifkan. Jika mahu, ia perlu perubahan kod kecil (disk media-library & laporan menjadi boleh-konfigur) — buat sebagai tugasan berasingan sebelum menukar `FILESYSTEM_DISK`.

---

## 9. Backup & pemantauan

- Forge › Server › *Backups*: **database harian** (simpan 14 hari) ke Spaces/S3.
- Backup fail `storage/app` mingguan (rsync / snapshot pelayan).
- Forge › Site › *Monitoring*: aktifkan amaran CPU/RAM/disk. (Pilihan: Sentry/Flare untuk ralat.)

---

## 10. Aliran keluaran (release)

```bash
# di develop: pastikan CI hijau (badge dalam README)
git checkout main
git merge --ff-only develop
git push origin main          # Forge Quick Deploy bermula
git checkout develop
```

Selepas deploy: log masuk sebagai Super Admin → semak `/dashboard`, jana satu PDF (resit), dan hantar satu ujian webhook.
