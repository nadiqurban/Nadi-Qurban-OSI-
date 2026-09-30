# Prompt untuk Claude Code — Nadi Qurban OSI

**Cara guna**
1. Buka folder projek `Nadi Qurban OSI` dalam Claude Code. Pastikan struktur ini ada:
   ```
   Nadi Qurban OSI/
   ├── CLAUDE.md                 ← memori projek (Claude Code baca automatik)
   ├── docs/PRD.md
   ├── docs/CLAUDE_CODE_PROMPTS.md (fail ini)
   └── design/                   ← 21 fail *.dc.html + uploads/ + screenshots/
   ```
2. Tampal **Prompt 0 (Master)** dahulu. Kemudian tampal prompt fasa **satu demi satu**. Jangan tampal semua sekaligus.
3. Selepas setiap fasa: semak di pelayar (desktop + telefon), kemudian `git commit`. Jika ada beza dengan design, beritahu Claude Code: *"Bandingkan dengan design/screenshots/X_d.png dan betulkan."*
4. Mod disyorkan: gunakan **Plan Mode** (Shift+Tab) untuk setiap fasa, luluskan pelan, kemudian biar ia laksana.

Prompt ditulis dalam Bahasa Inggeris supaya arahan teknikal lebih tepat; semua teks UI kekal Bahasa Melayu seperti design.

---

## Prompt 0 — Master (tampal sekali pada permulaan)

```
You are building "Nadi Qurban OSI", a production Laravel application, from a finished HTML prototype.

Read these first, fully, before writing any code:
1. CLAUDE.md (project rules, stack, design tokens, responsive rules, verification loop)
2. docs/PRD.md (requirements, routes, pipeline, data model, design gaps to fix in §12)
3. Skim every file in design/*.dc.html and look at design/screenshots/*.png

Non-negotiables:
- Stack: Laravel 13 (latest), PHP 8.4, MySQL 8.4, Blade, Livewire 4, Alpine, Tailwind v4, Vite, Pest 4.
- Desktop (≥1024px) must match the design 1:1: same layout, spacing, colours, font sizes, icons (Phosphor), labels and Malay copy. Take pixel values from the inline styles in the .dc.html templates and the style strings inside each file's renderVals() script.
- Fully mobile responsive (PRD §8). The prototype is NOT responsive; you must design the mobile behaviour using the rules in CLAUDE.md.
- Replace every localStorage bridge and dummy array with MySQL models, migrations, seeders (seed with the prototype's dummy data so screens look like the screenshots).
- Fix the design gaps listed in PRD §12.
- Enforce permissions server-side on every action.
- Work phase by phase. I will send each phase prompt separately. For each phase: propose a short plan, implement, write Pest tests, run the verification loop from CLAUDE.md, then give me a summary of what changed, what to check manually, and anything you could not match exactly.

Do not start coding yet. Reply with: (a) your understanding of the order pipeline in 8 lines, (b) the list of Blade UI components you will build in Phase 0, (c) any contradictions you found between design files that need my decision.
```

---

## Prompt 1 — Fasa 0: Setup, sistem reka bentuk & layout responsif

```
Phase 0 — project setup, design system, responsive app shell.

1. Create the Laravel 13 app in the current folder (keep CLAUDE.md, docs/, design/). PHP 8.4, MySQL 8.4 (DB name nadiqurban_osi). Install Livewire 4, Tailwind v4 (Vite), Pest 4 + browser plugin, Pint, Larastan, and the packages listed in CLAUDE.md. Set APP_LOCALE=ms, APP_TIMEZONE=Asia/Kuala_Lumpur, Carbon locale ms, SESSION_LIFETIME=30.
2. Assets: install @phosphor-icons/web@2.1.1 (regular + fill), @fontsource/inter (400-800), @fontsource/amiri via npm — no CDNs. Optimise the logos: design/uploads/"Untitled design.png" (sidebar/receipt logo), design/uploads/"Untitled design (2).png" (login/document logo), design/untitled-design-3--mrsyf3el-a388.png (certificate/tracking logo) → resize to 512px + 128px WebP/PNG in public/images/, plus favicon. Keep originals out of public/.
3. Tailwind v4 @theme with every token in CLAUDE.md (light) + dark tokens from Dashboard Operasi.dc.html (const T = dark ? …).
4. Build the Blade UI component library listed in CLAUDE.md (resources/views/components/ui). Match the design exactly:
   - button variants: primary (#42481c, hover #5a6127), secondary (white + border), success-green (#16A34A "Eksport Excel"), gold-outline, danger, icon-only; sizes from design (padding 11px 18px, 13.5px/600, radius 9px).
   - stat-card (42px tinted icon box + 22px value + 12.5px label), kpi-card with delta pill (Dashboard/Kewangan).
   - badge/status-badge driven by PHP enums, pill tabs with count bubble (active = #42481c bg white text; count bubble styles from the tabs() helpers in the JS), checkbox (20px, radius 6, checked = #42481c with ph-fill ph-check).
   - data-table: desktop CSS grid identical to the design; below md it renders each row as a stacked card (slots for title, badge, meta grid, actions). Option `scroll` for very wide tables (horizontal scroll + sticky first column).
   - modal: centred card (max-width from design, radius 14, header with 36px tinted icon + h2 16px + subtitle, close button 32px) → on <md a full-screen bottom sheet with sticky footer.
   - drawer, stepper (vertical workflow dots: done #42481c + check, current #C9A227, pending #F1F5F4), page-header (breadcrumb 12.5px + h1 23px + actions), filter bar (desktop inline; mobile "Tapis" button opening a bottom sheet), bulk-bar (desktop inline; mobile sticky bottom), empty-state, avatar (initials with colour pairs from design), doc-a4 / doc-a5 preview wrapper that scales to fit on small screens.
5. Layouts:
   - app: sidebar exactly as design (260px, logo block 72px, section labels 10.5px tracking 1.4px, item rowStyle with 3px active bar, gold badges, user footer) + header (search box with ⌘K hint, bell with red count, theme toggle, avatar + name + caret dropdown: Profil, Tetapan, Log Keluar). Below lg: sidebar becomes an Alpine off-canvas drawer with overlay; hamburger in header; search becomes an icon opening a full-screen search overlay.
   - auth (split panel, brand panel hidden on mobile) and public (tracking/portal) layouts.
   - Sidebar menu defined once in config/navigation.php (sections, labels, Phosphor icons, route, permission, badge resolver).
6. Create a hidden route /_design/components (local env only) showing every component in all states, at desktop and mobile, so I can review.
7. GitHub Actions workflow: pint --test, phpstan, pest.

Verify: components page renders at 1440 and 390 with no horizontal overflow; compare the sidebar/header against design/screenshots/Dashboard Operasi_d.png.
```

---

## Prompt 2 — Fasa 1: Auth, keselamatan, pengguna & RBAC, tetapan

```
Phase 1 — authentication, security, users & roles, settings. Design: design/Login.dc.html, design/Pengguna & Peranan.dc.html, and the settings views inside design/Tempahan & Pelanggan.dc.html (isSettingsProfil, isSettingsSyarikat, isSettingsRbac, showSettings modal).

1. Auth screens replicating all 6 Login views: login (email, password with eye toggle, "Ingat saya", "Lupa Kata Laluan?"), forgot, reset (4-bar strength meter Lemah/Sederhana/Baik/Kuat + 3 rules + mismatch message, exact colours from the script), forced password change, locked account (live countdown), login history. Use Laravel's built-in auth/password broker (Fortify headless is fine) with our own Blade/Livewire views. Brand panel stats come from DB.
2. Security: lock after 5 failed attempts for 15 min (RateLimiter + users.locked_until), 30-min idle timeout (also an Alpine idle timer that logs out), login_histories table (device/browser parsed from UA, IP, city if available, status Berjaya/Gagal/Sesi Semasa), must_change_password flag enforced by middleware, optional TOTP 2FA (Tetapan → Keselamatan).
3. Roles (spatie/laravel-permission): Super Admin, Admin HQ, Kewangan, Sales, Operasi, Vendor PIC. Modules = the 23 rows of the permission matrix in Pengguna & Peranan.dc.html (+Operasi column). Seed defaults exactly from that matrix (F=view+manage, V=view, N=none). Users can have multiple roles.
4. Pengguna & Peranan screen: Users tab (stats, search, role filter, table with edit + lock actions, Tambah Pengguna modal with multi-role chips + auto-generated temporary password, Edit Pengguna modal, "Akses & Keselamatan" modal: send reset link / force change / suspend), Roles tab (5→6 role cards, role detail with members + module permission pills, clickable Permission Matrix cycling Penuh→Lihat→Tiada + "Simpan Matriks", Edit Peranan modal).
5. Settings: the Tetapan modal (5 items) opened from the sidebar; routes /tetapan/profil (avatar upload, personal info, change password), /tetapan/syarikat (company details + bank, stored in a settings table and used by all documents), /tetapan/keselamatan (2FA, sessions, login history), /tetapan/notifikasi (link to preferences).
6. Sidebar hides modules the user cannot view; every Livewire action authorizes.
7. Activity log every login, failed login, role/permission change (Kritikal severity), user suspension.

Tests: lockout, idle timeout, forced change, permission matrix enforcement (a Sales user gets 403 on /kewangan manage actions), multi-role.
```

---

## Prompt 3 — Fasa 2: Data induk (negara, pakej, produk, pelanggan, kod promosi)

```
Phase 2 — master data. Design: design/Produk.dc.html, design/Kod Promosi.dc.html.

1. Migrations/models/seeders: countries (name, flag emoji, active; seed all countries appearing in any design file: Malaysia, Arab Saudi (Makkah), Indonesia, Thailand, India, Nigeria, Burkina Faso, Uganda, Chad, Bangladesh, Somalia, Palestin, Kemboja, Sudan), packages (Delima, Zamrud, Topaz, Nilam, Mutiara with sort order), enums Service (Qurban QB, Aqiqah AQ, Dam DM, Nazar Haiwan NZ) and Animal (Lembu LE cap 7, Kambing KA cap 1, Unta UN cap 7), customers (CUST-xxxxx, name, phone, email, address, postcode, city, state).
2. Produk screen: stats, service tabs, product cards exactly like the design (animal SVG mask icons cowMask/goatMask/camelMask from the script, package ribbon, code QB-LE-DEL, price, stock colour rule), Produk Baharu/Edit modal (add the missing Country field), soft delete. Product is the single source of price for orders and instalment plans (PRD §12.4). Seed the 12 products from the script.
3. Kod Promosi screen: stats, Aktif/Tamat Tempoh tabs, table with copy/edit/delete, modal with Auto code (NQ + 6 chars), type Peratus (%) or Jumlah Tetap (RM), value, usage limit, expiry. A PromoCode::discountFor(int $subtotalSen) method that handles both types correctly (fix the prototype bug where "RM 50" became 50%). Validation: active, not expired, under limit. Seed the 6 codes.
4. A PriceCalculator action used everywhere: unit price from product × quantity − promo discount (− deposit for instalments), all integer sen, with unit tests.

Responsive: product grid 3→2→1 columns; promo table → stacked cards on mobile.
```

---

## Prompt 4 — Fasa 3: Tempahan & Pelanggan + Pengesahan Bayaran

```
Phase 3 — orders. Design: design/Tempahan & Pelanggan.dc.html (list, detail, receipt, waybill, participant groups + A4 preview, new-order modal, proof viewer, bulk bar) and its payverify view. Reference output: design/uploads/KORBAN 2026 NQ-5 (1).pdf.

1. Models: orders (order_no NQ-{SVC}-{ANI}-{seq6}, tracking token, customer, product snapshot, service, animal, package, country, quantity, year, implementation_date, unit_price, subtotal, discount, total (sen), promo_code_id, payment_method FPX/Cek/Pindahan Bank, payment_type full/ansuran, status enum, stage enum from PRD §5), order_participants, order_stage_histories, payments (proof file via medialibrary, method, status pending/verified/rejected, verified_by/at). Generate numbers inside a transaction with a locked sequence table.
2. /tempahan list: 4 stat cards, period pills (Harian/Mingguan/Bulanan/Tahunan/Custom → date range), search, 4 dropdown filters (custom dropdown exactly like design, not native select, on desktop), reset, the 13-column table with ANSURAN badge, payment label pill, status badge, proof/view/edit actions, real pagination "Memaparkan 1–9 daripada 248 tempahan". Bulk bar: Eksport (Excel), Jana Senarai Peserta, Waybill, Generate Sijil, Diterima, Kemaskini Status ▾. Filters/tab in URL.
3. New order modal: every field from the design; choosing Produk auto-fills service/animal/package/country and price; quantity recalculates; promo datalist of active codes; summary Harga/Diskaun/Jumlah; payment type chips; proof upload (PNG/JPG/PDF ≤10MB). Creates customer if new. Validation messages in Malay.
4. /tempahan/{order} detail: tracking link card with copy (link to /jejak?track=…&t=token), customer info, order info, payment info (view/replace proof; PDF proofs previewed with pdf.js page 1), participants editor (qty > 1), 12-step workflow stepper with timestamps from order_stage_histories, Cetak Resit / Muat Turun / Kemaskini.
5. Documents (server-side PDF via spatie/laravel-pdf, company details from settings, QR SVG to tracking URL): order receipt A4, waybill A4 (one page per order, courier select, consignment prefix map EP/JT/DHL/GDEX/ARX/NV), participant groups A4 (Lembu/Unta 7 names per group, Kambing 1; header date + hashtags like the reference PDF). Print buttons open the PDF in a new tab.
6. /pengesahan-bayaran: tabs Belum/Telah Disahkan with counts, date filter, Excel export, receipt viewer modal, Sahkan → VerifyPayment action (payment verified, stage payment_verified, promo usage++, product stock−qty, event) / Batal → reject with reason. Only orders with status Diterima appear. Sidebar badge = pending count.
7. Excel exports via maatwebsite/excel with the same column headers and widths as the prototype.

Mobile: list rows become cards (no, name, phone, service·animal·country, price, payment pill, status, actions), bulk bar sticky bottom, filters in bottom sheet, new-order modal full-screen single column with sticky "Simpan Tempahan".
Tests: numbering uniqueness under concurrency, price+promo calc, verify/reject transitions, permissions.
```

---

## Prompt 5 — Fasa 4: Pipeline operasi + Jejak awam + Sijil

```
Phase 4 — operations pipeline. Design files: Lafaz Akad, Agihan Negara, Pelaksanaan & Laporan, AWB & Postage, Tempahan Selesai, Tracking Pelanggan, and the sijil editor view + sijil panel inside Tempahan & Pelanggan. Reference: design/uploads/MUHAMAD AIZUDDIN BIN MUHAMAD RIDZUAN QURBAN 1.pdf.

Implement each as its own route (PRD §4) using Actions + events:
1. /lafaz-akad: tabs, stats, bulk akad, customer detail modal (read → Edit → Simpan), akad modal with the exact text, ﷽ in Amiri, method chips Telefon/WhatsApp/Bersemuka, witness = current user, consent checkbox required (button grey/disabled until checked). akad_records table. → stage akad_done.
2. /agihan-negara: country summary cards, tabs, filters, per-row country select → vendor select limited to ACTIVE vendors of that country (vendors table comes in Phase 6 — create the migration + seed now with the 6 vendors from Vendor.dc.html), "Tiada vendor berdaftar untuk negara ini", Hantar, Butiran/Batal, Agih Pukal modal, group-by-vendor view, participant groups PDF, Excel. → stages country_assigned/vendor_assigned/executing.
3. /pelaksanaan: 3 tabs, filters, upload modal (multiple images, multiple videos mp4/webm/webp, notes) stored with medialibrary on the private disk, review mode with gallery + lightbox (image/video), Sahkan Selesai → report_verified + final_report. Vendor PIC users only see their allocated orders and can only upload.
4. /awb: tabs, service + date filters, 15-column table (horizontal scroll + sticky first column on mobile), Jana AWB modal (courier, post type, auto consignment, editable address), Postage Pukal, printable AWB preview/PDF with QR, Excel. shipments table (also used by the Tempahan "Waybill" action). → awb_generated, certificate_posted, completed, status Selesai.
5. /tempahan-selesai: stats, filters, table, detail modal with 5-step process timeline, Excel.
6. Public /jejak (layout public, no auth): search box, olive status card, participant list, 7-step progress + %, Jom Lafaz Akad box (Jawi text in Amiri), NOTA PENTING, help card (WhatsApp + phone from settings). Mask participant names unless the URL has the valid token; throttle 20 req/min/IP. Accepts ?track=.
7. /sijil editor: form + live A5 preview exactly like design (ornamental olive frame with gold hatch border, logo, fields, QR, numbers, footer), optional uploaded full-background template, Set Semula, Muat Turun PDF A5. Template saved in settings. Bulk "Generate Sijil" from Tempahan uses it (A5 — fix the A6 label), certificate numbers NQ-SIJIL-{year}-{seq4}, files stored in Dokumen → Sijil Qurban.

Tests: a full happy-path feature test moving one order from received → completed through every Action, asserting stage history and that each list shows it in the right tab. Browser test for /jejak at 390px.
```

---

## Prompt 6 — Fasa 5: Bayaran Ansuran + Portal + CHIP

```
Phase 5 — instalments. Design: design/Bayaran Ansuran.dc.html, design/Portal Ansuran.dc.html. Use the CHIP Collect skill/docs for the gateway (Purchases API, Brand ID + secret key, redirect + callback with RSA X-Signature verification).

1. Models: installment_plans (order link, customer, product snapshot, qty, total, deposit, promo, months 3/6/9/12, monthly amount, status berjalan/lewat/selesai/batal, cancel_reason, public pay_token, sent_at), installments (seq, due_date, amount, status, paid_at, method, gateway ref), payment_gateway_transactions.
2. /ansuran: 5 stats, tabs Berjalan/Selesai/Lewat Bayar/Batal/Semua, search + 4 filters, 13-column table with the segmented progress bar (click unpaid segment → "Sahkan Bayaran Diterima" modal with Perbankan Internet / Mesin Deposit Tunai; click paid segment → confirm undo), baki in red/green, row actions (WhatsApp reminder wa.me link with the prototype message, copy pay link modal, detail modal with per-share participant names, A4 receipt PDF, Hantar when complete). Bulk: Hantar Pukal, Batal with a reason modal (replace prompt()), Pulih.
3. Pelan Baharu modal: all fields, working tenure 3/6/9/12, deposit, promo, summary (Harga − Deposit − Diskaun = Baki; monthly = floor, remainder added to the last instalment), participants for qty > 1, payment method chips, manual transfer receipt upload. Due dates monthly from the first payment date.
4. "Hantar" on a completed plan creates the Order (payment_type ansuran, ANSURAN badge) into Pengesahan Bayaran.
5. Public /bayar/{token}: exactly the portal design (mobile-first, max-width 520px): balance card with progress, customer card, schedule with checkboxes on unpaid months, payment method list (FPX, DuitNow QR, Kad, E-Wallet), sticky "Bayar RM X Sekarang" → create CHIP purchase for selected months → redirect → success screen + A5 receipt; webhook /webhooks/chip verifies signature, is idempotent, marks installments paid, logs transaction. Failed/declined → stays "Perlu Bayar".
6. Scheduler: daily mark overdue plans Lewat Bayar; reminders H-3 and H+1 via mail (and WhatsApp link/notification). Settings for CHIP keys (encrypted) live in /tetapan/integrasi.

Tests: schedule maths incl. remainder, promo types, webhook signature verify + idempotency (fake CHIP), overdue job.
```

---

## Prompt 7 — Fasa 6: Vendor, PO, bayaran vendor, laporan, prestasi

```
Phase 6 — vendors. Design: design/Vendor.dc.html (list + PO Dicipta tab + profile with 6 tabs + modals + PO receipt).

1. Models: vendors (code SP 001 editable, vendor_id VND-xxxx, name, company, supplier, level Platinum/Gold/Silver/Bronze, status Aktif/Pending/Digantung, country, phone, email, animals json, bank fields, rank 1–10, soft deletes), vendor_rank_histories, purchase_orders (+items; NQ-PO-{year}-{seq4}, currency RM/USD, exchange rate snapshot from settings, status Draft/Sent/Accepted/In Progress/Completed/Cancelled, billing/shipping/notes, accepted_at/by), vendor_payments (Pending Payment/Payment Processing/Payment Completed, date, approver, bank, ref, receipt media, confirmed_by/at), vendor_reports (+media grouped by animal, verify checklist, status).
2. List: stats, filters, vendor cards exactly like design, ⋯ menu (Lihat Profil, Edit, Cipta PO, Nyahaktif, Buang), Daftar Vendor modal, Eksport; tab "PO Dicipta" table.
3. Profile tabs: Profil (info sections + Edit Maklumat modal + recent POs with RM/USD toggle), Purchase Order (list with checkboxes, create form, detail with editable rate table/billing/shipping/notes + Simpan, status flow stepper, Vendor Acceptance card, PO receipt A4 PDF), Bayaran (HQ only banner, records list, stepper, payment info form, bank chips, receipt upload/remove, "Sah Bayaran" only Super Admin/Admin HQ → PO appears in Kewangan "Rekod PO — Payment Completed"), Laporan (PO select, upload zones, files grouped by animal, preview popup, HQ Verify checklist, Sahkan & Tandakan Completed / Minta Semakan Semula), Prestasi (1–10 rank pills editable only by Super Admin — replace the prototype "Tukar ke Admin Sistem" demo toggle with real permission; tier auto from rank; 4 metrics computed from data; rank history), Audit Log (vendor-scoped activity).
4. Vendor PIC portal: users with role Vendor PIC linked to a vendor see only their own vendor profile tabs PO (Accept PO button), Laporan (upload), Bayaran (read-only). 
5. Agihan Negara vendor dropdown now uses this table (active + matching country).

Mobile: cards 3→2→1, profile tabs horizontally scrollable, PO detail sections stack.
```

---

## Prompt 8 — Fasa 7: Kewangan, CRM, Dokumen, Pusat Laporan, Audit Log, Notifikasi

```
Phase 7 — remaining modules. Design files: Kewangan, Sales CRM, Dokumen, Pusat Laporan, Audit Log, Notifikasi.

1. Kewangan: KPIs, Aliran Tunai grouped bar chart and Kaedah Bayaran donut rendered as server-side SVG Blade components (reproduce the geometry in the script: W 720, H 250, yMax, bar width, colours #42481c/#C9A227; donut 150px thickness 28), tabs, invoice table + detail page, Cipta Invois & Quotation modals (editable company block prefilled from settings, dynamic item rows, totals) → A4 preview → PDF, invoice status auto (Deposit/Dibayar/Tertunggak/Lewat via scheduler), Rekod PO — Payment Completed table + receipt viewer, Excel exports.
2. Sales CRM: Kanban with drag & drop (SortableJS + Livewire, persist stage + order), list view, KPIs, Lead Baharu modal, lead detail (info, notes textarea autosave, stage progress, activities + add note, Tandakan Selesai). Add "Tukar ke Tempahan" on closed leads (prefills new order modal).
3. Dokumen: grid/list toggle, stats, category rail with counts, storage meter (real usage vs configurable quota), search, service filter, sort, Muat Naik (multi-file) — auto-register system-generated PDFs (receipts, certificates, reports, PO receipts, payment receipts) into categories.
4. Pusat Laporan: 6 report templates + builder (type, date range, country chips, format PDF/XLSX/CSV) → queued job → "Laporan Dijana" list with Diproses/Siap + download.
5. Audit Log: spatie activitylog with severity (Info/Amaran/Kritikal) and action type icons; table, detail side panel, type summary bars, range filter, CSV export; immutable (no update/delete routes), prune after 24 months.
6. Notifikasi: database notifications for the 6 types, tabs, mark all read, preferences grid (App/Emel/WA toggles per type) respected by all notifications; header bell dropdown with latest 5 and live count (wire:poll 30s).
```

---

## Prompt 9 — Fasa 8: Dashboard

```
Phase 8 — Dashboard. Design: design/Dashboard Operasi.dc.html + screenshot.

Build every widget with real queries (cache 5 min, invalidated on order events): 9 KPI cards with period deltas, Jualan Bulanan (line+area+dashed target) and Pencapaian gauge (target from settings) as SVG Blade components reproducing the script geometry exactly, Agihan Negara map with d3 + topojson-client + world-atlas bundled via Vite (Alpine component, redraw on resize/theme), Ranking Vendor top 5, Prestasi Negara table, Aktiviti Terkini from activity log, Pakej bars, Haiwan tiles, Tempahan Terkini (8 rows, "Lihat semua" → /tempahan). Date filter, Excel export, Muat Semula, per-card collapse checkbox persisted per user, global dark mode toggle (tokens from the script, stored per user). Greeting uses the user's first name.
Mobile: KPIs 2 columns, charts full width, map height 180px, tables → cards.
```

---

## Prompt 10 — Fasa 9: Integrasi (API, Webhooks, gerbang tambahan, carian ⌘K)

```
Phase 9 — integrations. Design: the isApi and isWebhooks views + gateway modals in design/Pengguna & Peranan.dc.html.

1. /tetapan/integrasi: API keys via Sanctum (create/revoke, show once, prefix display), base URL, rate limit 120/min with usage meter, third-party connection cards, payment gateways CHIP IN / Billplz / toyyibPay (settings modals with show/hide secret, environment toggle for toyyibPay, enable/disable, status Aktif/Belum Sambung/Tidak Aktif), all secrets encrypted.
2. /api/v1 read endpoints: orders (by no/tracking), order status, products; write: create order (validated). OpenAPI description in docs/api.md.
3. /tetapan/webhooks: endpoints CRUD, signing secret, event subscriptions (payment.confirmed, order.completed, awb.generated, report.verified), delivery log (event, endpoint, HTTP code, time) using spatie/laravel-webhook-server with HMAC-SHA256 header X-NQ-Signature and retries.
4. Global ⌘K / Ctrl+K search (and the header search box): Livewire command palette searching orders (no, tracking, name, phone), customers, vendors, invoices, leads; keyboard navigation; full-screen on mobile.
```

---

## Prompt 11 — Fasa 10: QA responsif, keselamatan, prestasi

```
Phase 10 — hardening.

1. Responsive audit: write a Pest browser test that logs in as Super Admin and visits EVERY route at 390×844, 820×1180 and 1440×900; assert no console errors, no horizontal overflow, sidebar drawer works below 1024, every modal opens as a bottom sheet on mobile. Save screenshots to storage/app/qa/ and list any screen that still differs from design/screenshots.
2. Visual parity pass at 1440: for each screen compare with design/screenshots/<Screen>_d.png and fix spacing/colour/typography drift.
3. Run the laravel-security-audit skill over the codebase and fix all High/Critical findings (IDOR on orders/vendors/files, mass assignment, upload validation, signed URLs, rate limits, public tracking privacy, webhook signature checks).
4. Performance: add missing indexes, eager loading (enable Model::preventLazyLoading in non-prod), cache dashboard, queue PDFs/exports; Lighthouse mobile ≥ 90 performance on /jejak and /bayar/{token}.
5. Accessibility: labels, focus rings, aria for modal/drawer, colour contrast.
6. Seeders: a production-safe seeder (roles, permissions matrix, countries, packages, settings, one Super Admin from env) separate from the demo seeder.
```

---

## Prompt 12 — Push ke GitHub & publish ke Laravel Forge

```
Prepare deployment to GitHub + Laravel Forge.

1. Make sure .gitignore excludes .env, /vendor, /node_modules, /public/build, /storage/*.key and the large original design PNGs if not needed at runtime (keep design/*.html and screenshots in the repo for reference).
2. Initialise git if needed, create branches main and develop, commit with conventional messages, and push to the GitHub remote I give you: <PASTE_GITHUB_REPO_URL>. Use `gh` if available.
3. Write docs/DEPLOY.md with exact Forge steps:
   - Server: PHP 8.4, MySQL 8.4, Node LTS; create database + user.
   - Site: domain <osi.nadiqurban.com>, repo, branch main, Let's Encrypt SSL, Quick Deploy on.
   - Environment variables list (APP_*, DB_*, MAIL_*, FILESYSTEM_DISK=s3 + AWS_* for DigitalOcean Spaces, CHIP_*, SESSION_LIFETIME=30, APP_TIMEZONE=Asia/Kuala_Lumpur, QUEUE_CONNECTION=database).
   - Deploy script:
       cd $FORGE_SITE_PATH
       git pull origin $FORGE_SITE_BRANCH
       $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
       npm ci && npm run build
       ( flock -w 10 9 || exit 1; echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
       $FORGE_PHP artisan migrate --force
       $FORGE_PHP artisan optimize
       $FORGE_PHP artisan storage:link
       $FORGE_PHP artisan queue:restart
   - Daemon: php artisan queue:work --sleep=3 --tries=3 --timeout=300
   - Scheduler enabled (every minute).
   - Chromium + Puppeteer install for spatie/laravel-pdf (Browsershot) on Ubuntu, and set the node/npm/chrome paths in config.
   - Database backups daily, and CHIP webhook URL https://<domain>/webhooks/chip registered in the CHIP dashboard.
4. Add a GitHub Actions status badge and ensure CI passes before merging develop → main.
Do not put any secrets in the repo; ask me for them when needed.
```

---

## Prompt pembetulan (guna bila perlu)

- **UI tak sama:** `Open design/screenshots/<Skrin>_d.png and design/<Skrin>.dc.html. Take a screenshot of <route> at 1440×900 and list every visual difference (spacing, font size/weight, colour, radius, icon, copy). Then fix them all.`
- **Mobile rosak:** `At 390×844 the page <route> has <masalah>. Apply the responsive rules in CLAUDE.md and PRD §8, then re-run the overflow browser test.`
- **Logik salah:** `The behaviour of <butang/aliran> must match renderVals() in design/<Skrin>.dc.html and PRD §<n>. Write a failing Pest test first, then fix.`
- **Sambung sesi baru:** `Read CLAUDE.md, docs/PRD.md and git log -20. Tell me which phase we are in and what is left, then continue.`
