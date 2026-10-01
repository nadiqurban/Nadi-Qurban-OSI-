# Audit Keselamatan — Nadi Qurban OSI (Fasa 10)

Tarikh: 1 Okt 2026 · Skop: keseluruhan codebase (Laravel 13, Livewire 4, Sanctum, laluan awam `/jejak`, `/bayar/{token}`, webhook CHIP, API v1, webhook keluar). Kaedah: semakan 50 vektor (laravel-security-audit), jejak aliran data input → sink, dan ujian Pest yang gagal sebelum pembaikan (`tests/Feature/SecurityTest.php`).

## Ringkasan

| Severity | Dijumpai | Dibaiki |
|----------|---------:|--------:|
| Critical | 0 | — |
| High | 2 | 2 |
| Medium | 3 | 3 |
| Low | 2 | 1 (1 nota) |

---

## Penemuan

### [HIGH] Vendor PIC boleh baca data pelanggan vendor lain melalui ID tempahan  (vektor #21 IDOR)
- **Di mana:** `app/Livewire/Akad/Index.php` `openDetail()`, `app/Livewire/Allocation/Index.php` `openDetail()` / `groupIds()`, `app/Livewire/Shipping/Index.php`, `app/Livewire/Orders/Completed.php`, `app/Http/Controllers/OrderDocumentController.php` `selected()` (PDF senarai peserta / airway bill).
- **Isu:** Peranan *Vendor PIC* (akaun luar) ada akses Lihat pada Lafaz Akad, Agihan, AWB dan Tempahan Selesai. Kaedah `openDetail(int $orderId)` dan senarai AWB/Selesai memanggil `Order::query()->findOrFail($id)` tanpa skop vendor, jadi PIC boleh menukar ID dalam payload Livewire (atau `?ids[]=` pada PDF) dan nampak nama, telefon, emel, alamat dan senarai peserta semua pelanggan.
- **Bukti (sebelum):** `Order::query()->with(['customer','country'])->findOrFail($orderId);`
- **Pembaikan:** Global scope `App\Models\Scopes\VendorPicOrderScope` (`#[ScopedBy]` pada `Order`) — semasa Vendor PIC log masuk, *setiap* query Order hanya pulangkan tempahan yang diagihkan kepada vendornya (senarai, modal, route-model binding, PDF, carian ⌘K). Staf, API client, job queue dan `/jejak` tidak terjejas. PDF pilihan kosong kini 404.
- **Ujian:** `SecurityTest` › *stops a vendor PIC…*, *scopes shipping, completed orders and order PDFs…*, *keeps staff access unchanged*.

### [HIGH] Vendor PIC boleh buka semua fail Repositori Dokumen  (vektor #21 IDOR / #34)
- **Di mana:** `app/Livewire/Documents/Index.php`, `app/Http/Controllers/DocumentController.php::open()`.
- **Isu:** PIC ada `documents.view`; repositori mengandungi bukti bayaran pelanggan, resit bank vendor lain, invois dan sijil. `GET /dokumen/{id}/buka` hanya semak kebenaran modul.
- **Pembaikan:** `Document::scopeVisibleTo($user)` — PIC hanya nampak fail yang terikat pada `VendorPayment`, `VendorReport` dan `ExecutionReport` vendornya sendiri; digunakan pada senarai, kiraan, statistik, padam dan `open()` (404 jika bukan miliknya).
- **Ujian:** `SecurityTest` › *hides HQ documents from vendor PICs*.

### [MEDIUM] SSRF melalui URL endpoint webhook  (vektor #46)
- **Di mana:** `app/Livewire/Settings/Webhooks.php::save()`, `app/Support/Webhooks.php::dispatch()`.
- **Isu:** Pentadbir boleh mendaftar `http://169.254.169.254/…`, `http://127.0.0.1:…` atau IP RFC1918; pelayan akan POST ke rangkaian dalaman/metadata awan.
- **Pembaikan:** Peraturan `App\Rules\PublicUrl` (resolve DNS; tolak localhost, julat persendirian, link-local & reserved; HTTPS wajib dalam produksi) semasa simpan, dan disemak semula semasa hantar dalam produksi.
- **Ujian:** `SecurityTest` › *refuses webhook endpoints that point at internal addresses (SSRF)*.

### [MEDIUM] Tiada header keselamatan  (vektor #31, #42)
- **Isu:** Tiada `X-Frame-Options`/`frame-ancestors` (clickjacking), `nosniff`, `Referrer-Policy`, HSTS.
- **Pembaikan:** Middleware `App\Http\Middleware\SecurityHeaders` (web + api): `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, CSP `frame-ancestors/base-uri/form-action`, `Cross-Origin-Opener-Policy`, HSTS pada HTTPS produksi, buang `X-Powered-By`.

### [MEDIUM] HTTPS & kuki sesi tidak dipaksa dalam produksi  (vektor #13, #42)
- **Pembaikan:** `URL::forceScheme('https')` dalam produksi; `.env.example` mendokumenkan `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `APP_DEBUG=false` untuk Forge.

### [LOW] Gambar profil staf pada disk `public`
- Nota sahaja: avatar staf boleh dicapai jika URL diketahui (nama fail rawak). Tidak sensitif; dibiarkan.

### [LOW] Kebergantungan
- `composer audit`: tiada advisori. `npm audit --omit=dev`: 0 kelemahan.

---

## Disemak & bersih

- **SQLi (#1):** semua `selectRaw/whereRaw/orderByRaw` guna rentetan tetap atau binding (`Customer::resolve` guna `?`).
- **Command/SSTI/XXE (#3, #6, #7):** tiada `exec/eval/Blade::render`; tiada penghuraian XML input.
- **Mass assignment (#10, #22):** tiada `request()->all()`; semua write guna array tervalidasi; `role` tidak boleh ditetapkan melalui input.
- **Brute force / 2FA / reset (#11, #15, #17, #20):** lockout 5 cubaan + throttle per-IP; 2FA dicabar di pelayan (5 cubaan/5 min); reset guna Password broker dengan mesej generik; sesi diregenerasi selepas log masuk.
- **Kawalan akses (#23, #26):** setiap laluan modul `can:{module}.view`; setiap tindakan Livewire `authorize(...manage)`; API `auth:sanctum` + `abilities` + kunci dibatalkan ditolak.
- **XSS (#27, #28):** `{!! !!}` hanya untuk SVG QR / font yang dijana pelayan.
- **Muat naik (#33):** `mimes` + saiz pada setiap muat naik, disk `local` (peribadi), nama fail rawak, dihidang dengan `nosniff` + URL bertandatangan (bukti bayaran, fail vendor, media pelaksanaan).
- **Laluan awam:** `/jejak` throttle 20/min/IP termasuk melalui `?track=`, nama dimask kecuali token `hash_equals`; `/bayar/{token}` token 32–64 aksara, `#[Locked]`, throttle bayar 10/min & resit 30/min.
- **Webhook masuk CHIP:** tolak tanpa `X-Signature` RSA-SHA256 yang sah; gateway palsu hanya bila bukan produksi.
- **Webhook keluar:** HMAC-SHA256 `X-NQ-Signature`, secret disulitkan, putaran secret diaudit.
- **Rate limit API (#43):** 120/min setiap kunci.
- **Rahsia (#35):** `.env` dalam `.gitignore`; kunci gerbang disulitkan dalam `settings`.
- **Log audit:** tidak boleh diubah/dipadam (model events), prune 24 bulan.

## Tindakan semasa deploy (Forge)
1. `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `CHIP_FAKE=false`.
2. Pastikan nginx menyekat dotfiles dan tiada autoindex.
3. Jalankan `composer audit` dalam CI.
