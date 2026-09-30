# PRD — Nadi Qurban OSI (Operating System for Ibadah)

**Versi:** 1.0 · **Tarikh:** 30 Sep 2026 · **Pemilik produk:** Muhammad Nurfitkri (Superadmin)
**Sumber reka bentuk:** 21 fail `design/*.dc.html` (Claude Design) + aset `design/uploads/`
**Stack:** Laravel 13 (terkini) · PHP 8.4 · MySQL 8.4 LTS · Blade · Livewire 4 · Alpine.js · Tailwind CSS v4 · Vite
**Hosting:** GitHub (repo private) → Laravel Forge (auto-deploy dari branch `main`)

---

## 1. Ringkasan

Nadi Qurban OSI ialah sistem operasi dalaman Nadi Qurban Sdn. Bhd. (1677511-A) untuk mengurus keseluruhan kitaran ibadah **Qurban, Aqiqah, Dam & Nazar Haiwan** merentas beberapa negara pelaksanaan — dari tempahan & bayaran pelanggan, lafaz akad wakalah, agihan negara & vendor, pelaksanaan & laporan bukti, sehingga sijil dan AWB dipos kepada pelanggan. Sistem juga merangkumi vendor & Purchase Order, bayaran ansuran (dengan portal awam), kewangan (invois/quotation), CRM jualan, kod promosi, dokumen, laporan, audit log, notifikasi dan pengurusan pengguna (RBAC).

Reka bentuk UI telah siap sepenuhnya sebagai prototaip HTML interaktif (data dummy + `localStorage`). Projek ini menukarkan prototaip itu kepada aplikasi Laravel sebenar yang:

1. **Meniru 100% UI** (warna, tipografi, jarak, ikon, komponen, teks BM) pada desktop.
2. **100% mobile responsive** (prototaip asal *tidak* responsif — sidebar tetap 260px, jadual grid lebar).
3. Menggantikan semua data dummy/`localStorage` dengan pangkalan data MySQL, aliran kerja sebenar, kebenaran peranan, dan integrasi.
4. Membetulkan kekurangan & ketidakkonsistenan dalam prototaip (Seksyen 12).

## 2. Matlamat & Ukuran Kejayaan

| Matlamat | Ukuran |
|---|---|
| UI sama dengan design | Perbandingan screenshot 1440px setiap skrin — tiada perbezaan susun atur/warna yang ketara (semakan visual + Pest browser test) |
| Mobile responsive | Semua skrin boleh digunakan pada 360–430px tanpa skrol mendatar halaman; sasaran sentuh ≥ 44px; Lighthouse Mobile Accessibility ≥ 90 |
| Aliran kerja tanpa putus | Satu tempahan boleh bergerak dari *Tempahan Diterima* → *Selesai* sepenuhnya dalam sistem, dengan jejak audit setiap langkah |
| Prestasi | TTFB < 400ms (Forge), halaman senarai < 1.5s LCP pada 4G, pagination server-side |
| Keselamatan | Lockout 5 cubaan, sesi tamat 30 minit tidak aktif, kebenaran modul dikuatkuasakan pada server (Policy/Gate), audit log tidak boleh diubah |

**Bukan matlamat (fasa ini):** aplikasi mobile native, multi-syarikat (multi-tenant), e-dagang awam untuk pelanggan membuat tempahan sendiri (tempahan dibuat oleh staf / diimport).

## 3. Pengguna & Peranan

| Peranan | Penerangan | Akses utama |
|---|---|---|
| **Super Admin** | Pemilik sistem | Semua modul + Tetapan, Webhooks, Integrasi API, ranking vendor (dalam design: "Admin Sistem") |
| **Admin HQ** | Operasi HQ | Tempahan, pengesahan, akad, agihan, verify laporan, AWB, vendor, sijil |
| **Kewangan** | Finance | Bayaran ansuran, pengesahan bayaran, kewangan, bayaran vendor, laporan |
| **Sales** | Jualan | CRM, tempahan, pelanggan, kod promosi, produk |
| **Operasi** | Staf operasi | Agihan, pelaksanaan, AWB (wujud dalam chip peranan; tiada dalam matriks — ditambah) |
| **Vendor PIC** | Vendor luar | Hanya PO sendiri (terima PO), muat naik laporan pelaksanaan, lihat status bayaran sendiri |
| **Pelanggan (awam)** | Tanpa log masuk | Halaman *Jejak Status* & *Portal Bayaran Ansuran* melalui pautan bertoken |

Matriks kebenaran lalai = jadual dalam `Pengguna & Peranan.dc.html` (23 modul × 3 tahap: **Penuh / Lihat / Tiada**), boleh diubah oleh Super Admin dan disimpan dalam DB. Pengguna boleh ada **lebih daripada satu peranan** (chip multi-pilih dalam design).

## 4. Seni Bina Maklumat (Navigasi)

Sidebar (urutan & ikon Phosphor tepat seperti design; lencana kiraan adalah **dinamik**):

- **UTAMA:** Dashboard (`ph-squares-four`)
- **OPERASI:** Bayaran Ansuran (`ph-calendar-check`) · Tempahan & Pelanggan (`ph-shopping-cart-simple`, badge = tempahan aktif) · Pengesahan Bayaran (`ph-seal-check`, badge = menunggu) · Lafaz Akad (`ph-hand-heart`) · Agihan Negara (`ph-globe-hemisphere-west`) · Pelaksanaan & Laporan (`ph-shopping-bag`) · AWB & Postage (`ph-package`) · Tempahan Selesai (`ph-check-square-offset`) · Vendor (`ph-truck`) · Produk (`ph-cow`) · Dokumen (`ph-folders`)
- **JUALAN & KEWANGAN:** Sales CRM (`ph-users-three`) · Kod Promosi (`ph-ticket`) · Kewangan (`ph-wallet`, badge = invois tertunggak)
- **LAPORAN:** Pusat Laporan (`ph-chart-bar`) · Audit Log (`ph-clock-counter-clockwise`)
- **SISTEM:** Notifikasi (`ph-bell`, badge = belum dibaca) · Pengguna (`ph-users`) · Sijil (`ph-certificate`) · Tetapan (`ph-gear-six`, buka modal Tetapan)

Item sidebar disembunyikan jika peranan pengguna = *Tiada* untuk modul itu.

### Peta Laluan (routes)

| Laluan | Skrin design | Komponen Livewire |
|---|---|---|
| `/login`, `/lupa-kata-laluan`, `/reset-kata-laluan/{token}`, `/tukar-kata-laluan`, `/akaun-dikunci`, `/sejarah-log-masuk` | Login.dc.html (6 view) | `Auth\*` |
| `/` → `/dashboard` | Dashboard Operasi | `Dashboard` |
| `/ansuran` | Bayaran Ansuran | `Installments\Index` |
| `/tempahan`, `/tempahan/{order}` | Tempahan & Pelanggan (list, detail) | `Orders\Index`, `Orders\Show` |
| `/pengesahan-bayaran` | Tempahan & Pelanggan (view `payverify`) | `Payments\Verify` |
| `/lafaz-akad` | Lafaz Akad | `Akad\Index` |
| `/agihan-negara` | Agihan Negara | `Allocation\Index` |
| `/pelaksanaan` | Pelaksanaan & Laporan | `Execution\Index` |
| `/awb` | AWB & Postage | `Shipping\Index` |
| `/tempahan-selesai` | Tempahan Selesai | `Orders\Completed` |
| `/vendor`, `/vendor/{vendor}?tab=` | Vendor (list, profile 6 tab) | `Vendors\Index`, `Vendors\Show` |
| `/produk` | Produk | `Products\Index` |
| `/dokumen` | Dokumen | `Documents\Index` |
| `/crm`, `/crm/{lead}` | Sales CRM | `Crm\Pipeline`, `Crm\Show` |
| `/kod-promosi` | Kod Promosi | `Promo\Index` |
| `/kewangan`, `/kewangan/invois/{invoice}` | Kewangan | `Finance\Index`, `Finance\InvoiceShow` |
| `/laporan` | Pusat Laporan | `Reports\Index` |
| `/audit-log` | Audit Log | `Audit\Index` |
| `/notifikasi` | Notifikasi | `Notifications\Index` |
| `/pengguna`, `/pengguna/peranan/{role}` | Pengguna & Peranan | `Users\Index`, `Users\RoleShow` |
| `/sijil` | Tempahan (view `sijil`) — Editor Sijil | `Certificates\Editor` |
| `/tetapan/profil`, `/tetapan/syarikat`, `/tetapan/keselamatan`, `/tetapan/notifikasi`, `/tetapan/integrasi`, `/tetapan/webhooks` | Tempahan (settings views) + Pengguna & Peranan (api/webhooks views) | `Settings\*` |
| **Awam:** `/jejak?track=` | Tracking Pelanggan | `Public\Tracking` |
| **Awam:** `/bayar/{token}` | Portal Ansuran | `Public\InstallmentPortal` |
| **Webhook masuk:** `/webhooks/chip`, `/webhooks/billplz`, `/webhooks/toyyibpay` | — | Controller |

## 5. Aliran Kerja Teras (Pipeline Tempahan)

Prototaip menghubungkan halaman melalui `localStorage` (`nq_all_orders`, `nq_akad_orders`, `nq_assigned`, `nq_ready_awb`, `nq_completed_orders`, `nq_pipeline`, `nq_ansuran_orders`, `nq_promo_codes`, `nq_vendor_dir`, `nq_po_completed`). Dalam Laravel, semua ini menjadi **satu model `Order` dengan state machine** + jadual sejarah.

```
[Tempahan Baharu / Ansuran Selesai]
      │ status=Draf / Menunggu Bayaran / Dalam Proses
      ▼  (pukal: "Diterima")
 ① Pengesahan Bayaran ──Batal──► kembali ke senarai (status ditarik semula)
      │ Sahkan
      ▼
 ② Lafaz Akad (Telefon / WhatsApp / Bersemuka / Pukal + saksi + persetujuan)
      ▼
 ③ Agihan Negara (pilih negara → vendor aktif negara itu → Hantar / Agih Pukal)
      ▼
 ④ Pelaksanaan & Laporan (Upload Laporan → Menunggu Semakan HQ → Sahkan Selesai)
      ▼
 ⑤ AWB & Postage (Jana AWB / Postage Pukal → no. konsainan)
      ▼
 ⑥ Tempahan Selesai  (+ Sijil dijana & dipos)
```

**Enum `OrderStage`** (disimpan dengan cap masa dalam `order_stage_histories`):

| # | Stage (kod) | Label HQ (Aliran Kerja 12 langkah di detail tempahan) | Label pelanggan (Jejak, 7 langkah) |
|---|---|---|---|
| 0 | `received` | Tempahan Diterima | Tempahan Diterima |
| 1 | `payment_verified` | Pengesahan Bayaran | Bayaran Disahkan |
| 2 | `akad_done` | Akad | Lafaz Akad |
| 3 | `country_assigned` | Assign Negara | Agihan Negara |
| 4 | `vendor_assigned` | Assign Vendor | Agihan Negara |
| 5 | `executing` | Pelaksanaan | Agihan Negara |
| 6 | `report_uploaded` | Upload Laporan | Agihan Negara |
| 7 | `report_verified` | HQ Verify | Ibadah Dilaksanakan |
| 8 | `final_report` | Generate Final Report | Ibadah Dilaksanakan |
| 9 | `awb_generated` | Generate AWB | Sijil Dihantar |
| 10 | `certificate_posted` | Sijil Dipos | Sijil Dihantar |
| 11 | `completed` | Completed | Selesai |

**Status tempahan (lencana senarai):** Draf · Menunggu Bayaran · Diterima · Dalam Proses · Selesai · Dibatalkan (warna lencana tepat seperti design). Status berbeza daripada stage; stage hanya bergerak ke hadapan kecuali tindakan *Batal* yang eksplisit (dengan sebab & audit).

Setiap peralihan: dalam `DB::transaction`, rekod `order_stage_histories`, `activity_log`, cetus event (`OrderStageChanged`) → notifikasi & webhook keluar (`payment.confirmed`, `report.verified`, `awb.generated`, `order.completed`).

## 6. Keperluan Fungsian Mengikut Modul

> Setiap modul mesti meniru susun atur, label, placeholder, ikon, lencana dan keadaan kosong (*empty state*) dalam fail design yang dirujuk. Senarai di bawah menerangkan **tingkah laku** yang perlu berfungsi sebenar.

### 6.1 Log Masuk & Keselamatan (`Login.dc.html`)
- Panel kiri jenama (olive `#42481c`, logo, 3 ciri, 3 statistik — statistik ditarik dari DB: bil. negara aktif, vendor aktif, peserta musim semasa).
- View: Log Masuk (emel, kata laluan + toggle mata, *Ingat saya*), Lupa Kata Laluan (hantar pautan), Reset (meter kekuatan 4 bar: Lemah/Sederhana/Baik/Kuat + 3 peraturan + semakan padanan), Tukar Kata Laluan Wajib (pengguna baharu / dipaksa admin), Akaun Dikunci (kiraan detik sebenar), Sejarah Log Masuk (peranti, lokasi/IP, masa, status Berjaya/Gagal/Sesi Semasa).
- Lockout selepas **5 cubaan gagal** (15 minit), sesi tamat **30 minit** tidak aktif, 2FA TOTP pilihan (Tetapan → Keselamatan).

### 6.2 Dashboard (`Dashboard Operasi.dc.html`)
- 9 kad KPI (Jumlah Tempahan, Jumlah Peserta, Jumlah Jualan, Purata Nilai Tempahan, Jumlah Vendor, Bayaran Tertunggak, Laporan Tertunggak, Sijil Tertunggak, Pelaksanaan Akan Datang 30 hari) dengan delta % berbanding tempoh sebelum.
- Carta *Jualan Bulanan* (garisan + kawasan + garis sasaran putus-putus emas), gauge *Pencapaian Jualan* (sasaran musim boleh diset dalam Tetapan), peta *Agihan Negara* (d3 + topojson, bulatan berskala), *Ranking Vendor* top 5, jadual *Prestasi Negara*, *Aktiviti Terkini* (dari activity log), *Jumlah Mengikut Pakej* (bar), *Jumlah Mengikut Haiwan*, *Tempahan Terkini* 8 baris.
- Penapis tarikh, Eksport Excel, Muat Semula, checkbox pada setiap kad untuk sembunyi/runtuh (simpan pilihan per pengguna), toggle tema gelap/cerah (token gelap sudah ada dalam design).
- Salam "Assalamualaikum {nama pertama}".

### 6.3 Tempahan & Pelanggan (`Tempahan & Pelanggan.dc.html`)
- **Senarai:** 4 kad ringkasan, tab tempoh (Harian/Mingguan/Bulanan/Tahunan/Custom — Custom buka julat tarikh), carian (nama/telefon/no. tempahan/tracking), 4 dropdown penapis (Servis, Haiwan, Negara, Status) + Reset, jadual 13 lajur (checkbox, No. Tempahan + tracking + lencana ANSURAN, Pelanggan+telefon, Servis, Haiwan, Pakej, Negara, Kuantiti, Harga, Bayaran [FPX·Berjaya/FPX·Gagal/Cek/Pindahan Bank], Tahun, Status, Tindakan [lihat resit, lihat, edit]), pagination sebenar.
- **Bar tindakan pukal** (apabila ada pilihan): Eksport · Jana Senarai Peserta · Waybill · Generate Sijil · Diterima · Kemaskini Status ▾ · ✕.
- **Tempahan Baharu (modal):** nama, alamat, poskod, bandar, negeri (14 negeri), telefon, emel, **Produk** (auto-isi servis/haiwan/pakej/negara), servis, haiwan, pakej, kuantiti, harga auto (dari produk), negara, tahun & tarikh pelaksanaan, kod promosi (datalist kod aktif), ringkasan harga (Harga, Diskaun, Jumlah), jenis bayaran (FPX / Cek / Pindahan Bank), muat naik bukti bayaran (PNG/JPG/PDF). No. tempahan auto: `NQ-{SVC}-{ANI}-{seq 6 digit}`.
- **Detail:** pautan tracking + Salin, Maklumat Pelanggan, Butiran Tempahan, Maklumat Bayaran (lihat/tukar bukti; pratonton PDF halaman 1 via pdf.js), Senarai Peserta (jika kuantiti > 1; tambah/buang/edit), **Aliran Kerja** 12 langkah dengan masa, Cetak Resit / Muat Turun / Kemaskini.
- **Resit Tempahan (A4)**, **Waybill (A4, pilih kurier, satu halaman per tempahan)**, **Senarai Peserta Mengikut Kumpulan** (Lembu & Unta 7 nama/kumpulan, Kambing 1) + **Pratonton PDF A4** (format seperti `uploads/KORBAN 2026 NQ-5.pdf`: tarikh, hashtag `#KORBANLEMBU #seq`), Eksport Excel pelanggan (5 lajur seperti design).
- **Pengesahan Bayaran** (laluan sendiri): tab Belum/Telah Disahkan, penapis tarikh, Eksport Excel, lihat resit, Sahkan (→ stage 1, masuk Lafaz Akad) / Batal (tarik balik status Diterima).

### 6.4 Lafaz Akad (`Lafaz Akad.dc.html`)
Tab Menunggu Akad / Akad Selesai, 4 statistik, checkbox + **Akad Pukal**, modal *Detail Pelanggan* (lihat → Edit → Simpan; ringkasan bayaran; senarai peserta per bahagian), modal *Lafaz Akad Wakalah* (teks akad dengan nama & ibadah, ﷽ Amiri, kaedah Telefon/WhatsApp/Bersemuka, saksi/PIC = pengguna log masuk, checkbox persetujuan wajib sebelum *Sahkan Akad*). Rekod akad disimpan (kaedah, saksi, masa).

### 6.5 Agihan Negara (`Agihan Negara.dc.html`)
Kad ringkasan 4 negara teratas (+ %), tab Belum/Telah Diagih, penapis (carian no., Ibadah, Servis, Negara, Vendor), dropdown negara → dropdown vendor (hanya vendor **Aktif** bagi negara itu; mesej "Tiada vendor berdaftar untuk negara ini"), lencana Belum Agih / Perlu Vendor, butang Hantar, Butiran/Batal selepas dihantar, **Agih Pukal** (modal), **Kumpul ikut Vendor**, Jana Senarai Peserta + PDF A4, Eksport Excel. Menghantar agihan juga menambah tempahan kepada PO/tugasan vendor berkenaan.

### 6.6 Pelaksanaan & Laporan (`Pelaksanaan & Laporan.dc.html`)
3 tab (Menunggu Pelaksanaan / Menunggu Semakan / Selesai), penapis Servis/Negara/Vendor, modal Upload (gambar PNG/JPG berbilang, video MP4/WEBM/WEBP berbilang, nota) → Semak (galeri + lightbox imej/video) → **Sahkan Selesai** (→ masuk AWB). Vendor PIC boleh muat naik untuk tempahan yang diagih kepadanya sahaja. Fail disimpan di storan peribadi (S3/Spaces), dipapar dengan URL bertandatangan.

### 6.7 AWB & Postage (`AWB & Postage.dc.html`)
Tab Menunggu AWB / AWB Dijana, penapis Servis + tarikh, jadual 15 lajur (alamat, poskod, bandar, negeri, kurier, no. konsainan), modal *Jana Airway Bill* (kurier: Pos Laju, J&T Express, DHL Express, GDEX, Aramex, Ninja Van; jenis pos: Biasa/Ekspres/Berdaftar; no. konsainan auto `{prefix}{6 digit}MY`; alamat boleh edit), **Postage Pukal**, pratonton AWB boleh cetak, Eksport Excel. Menjana AWB → tempahan Selesai. (Fasa 2: integrasi EasyParcel untuk no. konsainan sebenar.)

### 6.8 Tempahan Selesai (`Tempahan Selesai.dc.html`)
4 statistik, penapis Servis/Negara/Tarikh, jadual 13 lajur, modal detail + *Garis Masa Proses* 5 langkah, Eksport Excel.

### 6.9 Jejak Status (awam) (`Tracking Pelanggan.dc.html`)
Tanpa sidebar; header jenama + Bantuan. Carian no. tracking, kad status olive, senarai peserta (jika > 1 bahagian), *Perkembangan Ibadah* 7 langkah + %, kotak *Jom Lafaz Akad* (teks Jawi Amiri + terjemahan), *NOTA PENTING*, bantuan WhatsApp + telefon (dari Tetapan). QR pada resit/sijil menuju ke sini.
**Privasi (penambahbaikan):** nama peserta dipapar separa bertopeng (cth. "Iskandar b*** Yusof") kecuali pautan mengandungi token rahsia; had kadar carian (throttle).

### 6.10 Bayaran Ansuran (`Bayaran Ansuran.dc.html`) & Portal (`Portal Ansuran.dc.html`)
- 5 statistik, tab Berjalan / Selesai / Lewat Bayar / Batal / Semua, carian + 4 penapis, jadual 13 lajur dengan **bar segmen kemajuan** (klik segmen → modal *Sahkan Bayaran Diterima* dengan kaedah Perbankan Internet / Mesin Deposit Tunai; klik segmen dibayar → batal tanda), Baki Belum Bayar (merah/hijau), ikon WhatsApp peringatan (mesej pratakrif), Salin Link Bayaran, Butiran (edit nama peserta per bahagian), Resit A4, **Hantar** (pelan selesai → cipta tempahan ke Pengesahan Bayaran dengan lencana ANSURAN), Hantar Pukal, Batal pukal dengan sebab (modal, bukan `prompt()`), Pulih.
- **Pelan Baharu:** seperti Tempahan Baharu + emel, **Tempoh 3/6/9/12 bulan (berfungsi)**, deposit, kod promosi, ringkasan (Harga − Deposit − Diskaun = Baki Ansuran; ansuran/bulan = baki ÷ tempoh, dibundarkan, baki sen pada bulan terakhir), senarai peserta, kaedah (FPX Auto-debit / Kad Kredit / Manual Transfer + resit).
- **Portal awam `/bayar/{token}`:** satu pautan kekal per pelan; ringkasan baki, jadual ansuran (checkbox pilih beberapa bulan), pilih kaedah (FPX / DuitNow QR / Kad / E-Wallet), *Bayar RM X Sekarang* → **CHIP Collect** purchase → redirect → callback (sahkan tandatangan) → tandakan dibayar secara automatik → skrin *Bayaran Berjaya* + resit (A5 PDF). Ditolak → kekal *Perlu Bayar*.
- Scheduler harian: tandakan *Lewat Bayar* jika tarikh lepas; hantar peringatan (emel/WhatsApp) H-3 dan H+1.

### 6.11 Vendor (`Vendor.dc.html`)
- **Senarai:** tab Vendor / PO Dicipta, 4 statistik, carian + penapis (Negara, Jenis Haiwan, Status), kad vendor (inisial berwarna, negara, status, kod `SP 001`, chip haiwan, PO Aktif, % Siap, Rating, PO Semasa + status), menu ⋯ (Lihat Profil, Edit, Cipta PO, Nyahaktif, Buang — soft delete), Eksport, **Daftar Vendor** (modal: kod boleh edit, syarikat, supplier, negara, telefon, emel, jenis haiwan multi, tahap Platinum/Gold/Silver/Bronze, maklumat bank + SWIFT).
- **Profil (6 tab):** *Profil* (maklumat + Edit Maklumat + PO terkini dengan toggle RM/USD); *Purchase Order* (senarai, Cipta PO `NQ-PO-{tahun}-{seq4}`, detail dengan jadual kadar boleh edit, billing/shipping/notes boleh edit, status flow Draft→Sent→Accepted→In Progress→Completed / Cancelled, Vendor Acceptance, Cetak/Jana Resit PO A4); *Bayaran* (HQ sahaja: rekod bayaran, stepper Pending→Processing→Completed, maklumat bayaran, bank chip, no. rujukan, muat naik resit bank JPG/PNG/PDF, **Sah Bayaran** oleh Super Admin/Admin HQ); *Laporan* (Report Submission per PO, fail dikumpul ikut haiwan, pratonton, HQ Verify checklist, Sahkan & Tandakan Completed / Minta Semakan Semula); *Prestasi* (ranking 1–10 hanya Super Admin boleh ubah, tier automatik, 4 metrik, sejarah ranking); *Audit Log* vendor.
- PO dengan bayaran Completed → muncul dalam Kewangan *Rekod PO — Payment Completed*.
- Kadar USD boleh diset dalam Tetapan (design guna 4.7 tetap).

### 6.12 Produk (`Produk.dc.html`)
Katalog kad (ikon haiwan SVG mask lembu/kambing/unta, lencana pakej, kod `QB-LE-DEL`, harga, stok berwarna: 0 merah, <20 oren, lain hijau), tab servis, Produk Baharu/Edit/Buang. **Produk ialah sumber tunggal harga** untuk Tempahan & Ansuran. Tambah medan Negara (ada dalam state, hilang dari borang design). Stok berkurang apabila tempahan disahkan.

### 6.13 Dokumen (`Dokumen.dc.html`)
Paparan grid/senarai, 4 statistik, rel kategori (Semua, Sijil Qurban, Laporan Pelaksanaan, Invois & Resit, Video & Foto, Perjanjian Vendor, Upload Payment Receipt) + kiraan, meter storan, carian, penapis servis, susun Terkini, **Muat Naik** (berfungsi), muat turun/pratonton. Dokumen yang dijana sistem (resit, sijil, laporan, PO) didaftarkan automatik ke kategori berkenaan.

### 6.14 Sales CRM (`Sales CRM.dc.html`)
Paparan Kanban (5 lajur: Lead Baru, Dihubungi, Rundingan, Cadangan, Ditutup — **drag & drop** dengan SortableJS/Alpine, simpan ke DB) & Senarai, 4 KPI, Lead Baharu (modal), detail lead (maklumat, catatan, kemajuan peringkat, aktiviti & nota, Tandakan Selesai). Lead Ditutup boleh "Tukar ke Tempahan" (penambahbaikan).

### 6.15 Kod Promosi (`Kod Promosi.dc.html`)
Tab Aktif / Tamat Tempoh, 4 statistik, jadual (Salin, Edit, Buang), modal kod (Auto-jana `NQxxxxxx`, jenis **Peratus / Jumlah Tetap RM**, nilai, had guna, sah hingga). Kiraan diskaun mesti ikut jenis (bug design: `RM 50` dikira 50%). Validasi: aktif, belum tamat, belum capai had. `used_count` bertambah apabila tempahan disahkan.

### 6.16 Kewangan (`Kewangan.dc.html`)
4 KPI, carta *Aliran Tunai* (bar berkumpulan Kutipan vs Bayaran vendor), donut *Kaedah Bayaran*, tab Semua/Dibayar/Tertunggak, carian + Status, jadual invois + detail invois (item, jumlah, ringkasan bayaran, rekod transaksi, Sahkan Bayaran), **Cipta Invois** & **Quotation** (maklumat syarikat boleh edit, item berbilang, jumlah, tarikh tempoh, nota → Pratonton PDF A4 → Cipta), jadual *Rekod PO — Payment Completed* + pemapar resit, Eksport Excel. Status invois auto: Deposit / Dibayar / Tertunggak / Lewat (> tarikh tempoh).

### 6.17 Pusat Laporan (`Pusat Laporan.dc.html`)
6 templat laporan pantas (Ringkasan Jualan, Prestasi Negara, Prestasi Vendor, Kewangan & Aliran Tunai, Status Pensijilan, Peserta & Ibadah), *Bina Laporan* (jenis, julat tarikh, chip negara, format PDF/XLSX/CSV) → dijana dalam **queue** → senarai *Laporan Dijana* (Diproses → Siap, muat turun).

### 6.18 Audit Log (`Audit Log.dc.html`)
4 statistik, carian, tab keterukan (Semua/Info/Amaran/Kritikal), jadual + panel butiran (Log ID, pengguna, tindakan, butiran, IP, masa), ringkasan ikut jenis, julat masa, Eksport CSV. Log **immutable** & disimpan 24 bulan (prune terjadual).

### 6.19 Notifikasi (`Notifikasi.dc.html`)
Tab Semua/Belum Dibaca/Tempahan/Kewangan/Sistem, feed, *Tanda semua dibaca*, panel Ringkasan / **Keutamaan Notifikasi** (6 jenis × App/Emel/WA toggle, disimpan per pengguna). Loceng header menunjukkan kiraan sebenar + dropdown ringkas.

### 6.20 Pengguna & Peranan (`Pengguna & Peranan.dc.html`)
Tab Pengguna / Peranan & Kebenaran; statistik, carian + penapis peranan, jadual pengguna (edit, akses & keselamatan: set semula kata laluan / paksa tukar / gantung), Tambah Pengguna (multi peranan, kata laluan sementara auto-jana), kad peranan → detail peranan (ahli, kebenaran modul), **Matriks Kebenaran** klik-kitar Penuh→Lihat→Tiada + Simpan Matriks, Edit Peranan.
Sub-skrin **Integrasi API** (kunci API Sanctum, base URL, had kadar, sambungan pihak ketiga, gerbang pembayaran CHIP IN / Billplz / toyyibPay dengan modal tetapan + aktif/nyahaktif) dan **Webhooks** (endpoint, signing secret, peristiwa, log penghantaran).

### 6.21 Sijil (Editor) & Tetapan
- **Editor Sijil:** borang (tajuk, ayat pembuka, nama, ayat penghubung, ibadah, butiran, lokasi, tarikh, ayat penutup, URL QR, no. sijil, no. tracking, template latar penuh muat naik) + **pratonton A5 langsung** (148×210mm) → Muat Turun PDF A5. Templat disimpan sebagai tetapan; *Generate Sijil* pukal dari senarai tempahan menggunakan templat ini (no. sijil `NQ-SIJIL-{tahun}-{seq4}`). Rujukan: `uploads/MUHAMAD AIZUDDIN ... QURBAN 1.pdf`.
- **Modal Tetapan** (5 item): Profil & Akaun, Notifikasi, Pengguna & Peranan, Maklumat Syarikat (nama, SSM, SST, telefon, emel, alamat, laman web, bank — digunakan pada semua resit/PO/invois/sijil), Keselamatan (2FA, sejarah log masuk, sesi aktif).

## 7. Keperluan UI & Sistem Reka Bentuk

**Token (wajib tepat):**

| Token | Nilai | Guna |
|---|---|---|
| `primary` | `#42481c` | butang utama, aktif nav, teks no. tempahan, avatar |
| `primary-hover` | `#5a6127` | hover butang |
| `primary-soft` | `#edeee0` | latar nav aktif, tint ikon |
| `gold` | `#C9A227` | badge sidebar, sasaran, aksen; `gold-soft #FBF3DC`, `gold-ink #9a7d16` |
| `bg` | `#F6F8F7` | latar aplikasi, header jadual |
| `surface` | `#FFFFFF` | kad |
| `border` | `#E2E8F0` | semua border; `divider #F1F5F4` |
| `ink` | `#1A1D21` · `ink-2 #334155` · `ink-3 #475569` · `muted #64748B` · `faint #94A3AC` | teks |
| success | `#16A34A` / `#EAF7EE` | |
| info | `#2563EB` / `#E8F0FB` | |
| warning | `#D97706` / `#FEF3E2` | |
| danger | `#DC2626` / `#FDECEC` | |
| purple | `#7C3AED` / `#F3ECFB` | |
| neutral badge | `#64748B` / `#F1F5F4` | |

- **Tipografi:** Inter 400–800 (self-host), Amiri untuk teks Arab/Jawi. Saiz lazim: H1 23px/700, H2 16px/700, badan 13–13.5px, label 12px/600, header jadual 11px/700 uppercase tracking .4px, lencana 10.5–11px/700.
- **Bentuk:** radius butang/input 9px, kad 12px, modal 14px, lencana 6px/20px (pill), checkbox 20×20 radius 6px. Bayang modal `0 24px 60px rgba(0,0,0,.3)`; overlay `rgba(20,24,20,.55)`.
- **Layout:** sidebar 260px putih, header 72px sticky, kandungan padding `28px 32px 56px`.
- **Ikon:** Phosphor Icons v2.1.1 (regular + fill) melalui npm.
- **Dokumen cetak** (resit, waybill, AWB, PO, invois, quotation, senarai peserta, sijil) mengikut design dan dijana sebagai PDF di server.

## 8. Keperluan Mobile Responsive (wajib)

Breakpoint: `sm 640` · `md 768` · `lg 1024` · `xl 1280`. Desktop (≥1024) = design asal tepat.

| Elemen | < 1024px (tablet) | < 768px (telefon) |
|---|---|---|
| Sidebar | Off-canvas drawer (kiri, 280px, overlay, tutup bila navigasi/klik luar/Esc), butang hamburger di header | Sama |
| Header | Hamburger + logo kecil; carian jadi ikon → overlay carian skrin penuh | Nama pengguna disembunyikan, hanya avatar |
| Tajuk halaman | Butang tindakan balut ke baris baharu | Butang utama lebar penuh; butang sekunder masuk menu ⋯ |
| Grid KPI/stat | 4→2 lajur | 2 lajur (nilai 18–20px); 9 KPI dashboard = 2 lajur |
| Jadual senarai | Skrol mendatar dalam kad dengan lajur pertama sticky | **Kad bertindan**: baris 1 checkbox + no. + lencana status; baris 2 nama/telefon; grid 2 lajur medan penting; tindakan di bawah. Jadual sangat lebar (AWB 15 lajur, Tempahan Selesai) guna skrol mendatar + sticky kolum no. |
| Penapis | Balut | Butang "Tapis" → bottom sheet dengan semua dropdown + Reset/Guna |
| Tab/pill | Baris boleh skrol mendatar (snap) | Sama |
| Bar tindakan pukal | Balut | Bar sticky di bawah skrin dengan kiraan + menu tindakan |
| Modal | Lebar max seperti design | **Skrin penuh / bottom sheet**, header & footer butang sticky, borang 1 lajur |
| Kanban CRM | Lajur skrol mendatar | Lajur 85vw dengan scroll-snap |
| Carta | SVG `viewBox` skala | Donut + legenda bertindan; peta tinggi 180px |
| Profil vendor tab (6) | Skrol mendatar | Sama |
| Pratonton A4/A5 | Skala-muat (CSS transform) dalam bekas | Sama + butang muat turun PDF menonjol |
| Login | Panel jenama 40% | Panel jenama disembunyikan; logo + tajuk di atas borang |
| Jejak & Portal awam | Sudah satu lajur | Grid meta 3→1 lajur; butang Bayar sticky bawah |

Lain-lain: sasaran sentuh ≥ 44px, input font ≥ 16px pada telefon (elak zoom iOS), `safe-area-inset`, tiada skrol mendatar pada `body`.

## 9. Model Data (ringkas)

`users`, `roles/permissions` (spatie), `login_histories`, `settings` (kunci-nilai; rahsia disulitkan), `countries`, `packages`, `products`, `customers`, `orders`, `order_participants`, `order_stage_histories`, `payments` (bukti, kaedah, status, pengesah), `akad_records`, `allocations`, `execution_reports` (+ media), `shipments` (AWB), `certificates`, `installment_plans`, `installments`, `promo_codes`, `promo_redemptions`, `vendors`, `vendor_rank_histories`, `purchase_orders`, `purchase_order_items`, `vendor_payments`, `vendor_reports` (+ media), `leads`, `lead_activities`, `invoices`, `invoice_items`, `quotations`, `quotation_items`, `documents` (spatie/medialibrary), `report_exports`, `notifications`, `notification_preferences`, `activity_log`, `personal_access_tokens`, `webhook_endpoints`, `webhook_deliveries`, `payment_gateway_transactions`.

Enum PHP: `Service` (Qurban QB, Aqiqah AQ, Dam DM, Nazar Haiwan NZ), `Animal` (Lembu LE kapasiti 7, Kambing KA 1, Unta UN 7), `OrderStatus`, `OrderStage`, `PaymentMethod`, `PoStatus`, `VendorLevel`, `VendorStatus`, `InvoiceStatus`, `LeadStage`, `Courier`, `PostType`.

Penomboran (unik, dijana dalam transaksi dengan kunci baris): tempahan `NQ-QB-LE-001248`, pelanggan `CUST-10240`, PO `NQ-PO-2027-0148`, invois `INV-2027-0891`, quotation `QUO-2027-0076`, sijil `NQ-SIJIL-2027-0248`, waybill `NQ-WB-2027-1248`, resit ansuran `NQRCPT001243`, vendor `SP 001` / `VND-2010`, lead `LEAD-5040`.

## 10. Integrasi

| Integrasi | Tujuan | Fasa |
|---|---|---|
| **CHIP Collect** | Kutipan bayaran portal ansuran (FPX, kad, e-wallet, DuitNow QR) + callback bertandatangan (RSA) | 1 |
| Billplz / toyyibPay | Gerbang alternatif (tetapan sedia, boleh aktif/nyahaktif) | 2 |
| WhatsApp (pautan `wa.me` dahulu; WhatsApp Cloud API fasa 2) | Peringatan ansuran, notifikasi status | 1 / 2 |
| Emel (SMTP/Postmark/SES) | Reset kata laluan, notifikasi, resit | 1 |
| EasyParcel | No. konsainan & tracking kurier sebenar | 2 |
| Webhooks keluar (HMAC-SHA256 `X-NQ-Signature`) | `payment.confirmed`, `order.completed`, `awb.generated`, `report.verified` | 2 |
| API (Sanctum, `/api/v1`, 120 req/min) | Integrasi ERP/CRM | 2 |

## 11. Keperluan Bukan Fungsian

- **Keselamatan:** Policy per modul (Penuh/Lihat), CSRF, validasi Form/Livewire, upload terhad (jenis MIME, saiz: imej 5MB, PDF 10MB, video 200MB), storan peribadi + URL bertandatangan, rahsia gerbang disulitkan (`encrypted` cast), rate limit login/jejak/portal, header keselamatan, tiada data sensitif dalam log. Lulus skill *laravel-security-audit* sebelum produksi.
- **Prestasi:** eager loading, indeks pada `order_no`, `stage`, `status`, `country_id`, `vendor_id`, `created_at`; cache KPI dashboard 5 minit; PDF & Excel berat melalui queue.
- **Lokaliti:** `APP_LOCALE=ms`, zon waktu `Asia/Kuala_Lumpur`, format tarikh "12 Jun 2027", bulan BM (Jan, Feb, Mac, Apr, Mei, Jun, Jul, Ogos, Sep, Okt, Nov, Dis), mata wang `RM 2,450` / ringkas `RM 3.82j`, `RM 168k`.
- **Kebolehaksesan:** label pada input, fokus kelihatan, kontras AA, `aria` pada modal/drawer.
- **Ujian:** Pest (unit + feature untuk setiap peralihan stage, kiraan harga/diskaun/ansuran, kebenaran), Pest browser test untuk aliran utama pada 1440px dan 390px.
- **Operasi:** backup DB harian (Forge), queue worker + scheduler, log ralat (Sentry/Flare pilihan).

## 12. Kekurangan Dalam Design & Pelarasan (“adjust mana yang berkurang”)

1. **Tidak responsif** → Seksyen 8.
2. **Semua data dummy/`localStorage`** → DB + state machine (Seksyen 5, 9).
3. **Format no. tempahan bercampur** (`NQ-2027-001248`, `NQ-QB-LE-001248`, `NQT-2027-…`) → satu format `NQ-{SVC}-{ANI}-{seq}`; tracking guna no. yang sama + token rahsia pautan.
4. **Harga tidak konsisten** (Lembu 2,450 dalam tempahan vs 3,500 produk vs 2,700/bhg dalam akad) → harga dari jadual `products` sahaja, disalin (snapshot) ke tempahan.
5. **Senarai negara/vendor/pakej/peranan berbeza-beza antara halaman** → jadual induk (countries, vendors, packages) & peranan tetap (Super Admin, Admin HQ, Kewangan, Sales, Operasi, Vendor PIC).
6. **Bug diskaun promo** jenis RM dikira sebagai % → kira ikut jenis.
7. **Tempoh ansuran tidak berfungsi** (sentiasa 6 bulan) → 3/6/9/12 berfungsi.
8. **Batal ansuran guna `prompt()`** → modal sebab pembatalan.
9. **Pengesahan Bayaran, Editor Sijil & Tetapan tersorok dalam halaman Tempahan** → laluan sendiri.
10. **Sijil dilabel A6 tetapi dijana A5** → seragamkan A5.
11. **Waybill (Tempahan) bertindih dengan AWB** → satu entiti `Shipment`; butang Waybill dalam Tempahan menggunakan data AWB.
12. **QR guna `api.qrserver.com` & PDF guna html2pdf di pelayar** → QR dijana di server (SVG), PDF di server (kualiti konsisten, boleh dilampir emel).
13. **Logo 6250×6250px (3.8MB)** → optimumkan ke 512px WebP/PNG + favicon.
14. **Carian ⌘K, pagination, penapis tempoh, butang "Lihat semua", "Laporan Tersuai", "Muat Naik" dokumen tidak berfungsi** → dilaksanakan.
15. **Tiada paparan untuk Vendor PIC** → portal vendor terhad (PO sendiri, Accept PO, upload laporan, status bayaran).
16. **Mod gelap hanya di Dashboard** → toggle global (token gelap), disimpan per pengguna (Fasa 2 jika masa terhad).
17. **Jejak awam dedah nama penuh** → penyamaran separa + token + throttle.
18. **Kadar USD tetap 4.7** → tetapan kadar tukaran.
19. **Produk tiada medan Negara dalam borang** → ditambah.
20. **Peranan "Operasi" tiada dalam matriks** → ditambah.

## 13. Fasa Penghantaran

| Fasa | Skop | Anggaran |
|---|---|---|
| 0 | Setup projek, sistem reka bentuk (komponen Blade), layout responsif, aset, CI | 3 hari |
| 1 | Auth & keselamatan, pengguna & RBAC, Tetapan (profil, syarikat) | 4 hari |
| 2 | Data induk: negara, pakej, produk, kod promosi, pelanggan | 3 hari |
| 3 | Tempahan (senarai, baharu, detail, pukal, resit, waybill, senarai peserta) + Pengesahan Bayaran | 6 hari |
| 4 | Lafaz Akad, Agihan Negara, Pelaksanaan & Laporan, AWB, Tempahan Selesai, Jejak awam, Sijil | 7 hari |
| 5 | Bayaran Ansuran + Portal + CHIP + scheduler peringatan | 5 hari |
| 6 | Vendor (profil 6 tab, PO, bayaran vendor, laporan, prestasi) | 6 hari |
| 7 | Kewangan, CRM, Dokumen, Pusat Laporan, Audit Log, Notifikasi | 6 hari |
| 8 | Dashboard (KPI, carta, peta) | 3 hari |
| 9 | Integrasi (API, webhooks, gerbang tambahan) | 3 hari |
| 10 | QA responsif, ujian, audit keselamatan, deploy Forge | 4 hari |

## 14. Penerimaan (Definition of Done) per skrin

- [ ] Desktop 1440px sepadan dengan design (screenshot bersebelahan disemak).
- [ ] Telefon 390px & tablet 820px: tiada skrol mendatar halaman, semua tindakan boleh dicapai.
- [ ] Semua butang/penapis/tab/modal dalam design berfungsi dengan data DB.
- [ ] Kebenaran dikuatkuasakan (Penuh/Lihat/Tiada) + ujian feature.
- [ ] Keadaan kosong, loading (`wire:loading`), ralat validasi dalam BM.
- [ ] Tindakan penting direkod dalam Audit Log.

## 15. Deploy (GitHub → Forge)

1. Repo GitHub private `nadiqurban/osi`, branch `main` (produksi) & `develop` (staging). GitHub Actions: Pint, Larastan, Pest.
2. Forge: pelayan PHP 8.4 + MySQL 8.4 + Node LTS; site `osi.nadiqurban.com`, SSL Let's Encrypt, *Quick Deploy* dari `main`.
3. Deploy script: `composer install --no-dev -o` → `npm ci && npm run build` → `php artisan migrate --force` → `optimize` → `queue:restart`.
4. Daemon: `php artisan queue:work --tries=3 --timeout=300`; Scheduler: `schedule:run` setiap minit.
5. Storan fail: DigitalOcean Spaces / S3 (disk `s3` peribadi). Chromium/Puppeteer dipasang di pelayan untuk PDF (Browsershot).
6. Env produksi: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_LIFETIME=30`, `APP_TIMEZONE=Asia/Kuala_Lumpur`, kunci CHIP, SMTP.
7. Backup DB harian + pemantauan uptime.

## 16. Soalan Terbuka

1. Domain produksi & emel pengirim rasmi?
2. Gerbang pembayaran utama: sahkan **CHIP** (portal design sebut "Dikuasakan CHIP IN").
3. Adakah vendor luar akan log masuk sendiri (portal vendor) pada fasa 1?
4. Storan fail: Spaces/S3 atau cakera pelayan Forge?
5. Data sedia ada (Excel tempahan 2026) perlu diimport?
6. Sasaran jualan musim & tahun musim semasa (design: RM 5.00 juta, 2027).
