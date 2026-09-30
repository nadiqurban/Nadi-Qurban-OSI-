# CLAUDE.md — Nadi Qurban OSI

Project memory for Claude Code. Read this first in every session, then `docs/PRD.md`.

## What this is
Internal operations system for Nadi Qurban Sdn. Bhd. (Qurban / Aqiqah / Dam / Nazar Haiwan). We are converting a finished HTML prototype (`design/*.dc.html`) into a production Laravel app. **The UI must match the design 1:1 on desktop and be fully responsive on mobile.** All UI text is Bahasa Melayu, exactly as written in the design.

## Stack (do not change without asking)
- Laravel 13 (latest), PHP 8.4, MySQL 8.4
- Blade + Livewire 4 (+ Alpine.js bundled with Livewire), Tailwind CSS v4 via Vite
- Pest 4 (unit, feature, browser tests), Laravel Pint, Larastan level 6
- Packages: spatie/laravel-permission, spatie/laravel-activitylog, spatie/laravel-medialibrary, spatie/laravel-pdf (Browsershot), maatwebsite/excel, simplesoftwareio/simple-qrcode (SVG), laravel/sanctum, spatie/laravel-webhook-server, pragmarx/google2fa-laravel (2FA)
- Frontend npm: @phosphor-icons/web@2.1.1, @fontsource/inter, @fontsource/amiri, d3 + topojson-client + world-atlas (dashboard map only), sortablejs (CRM kanban), pdfjs-dist (payment-proof preview)
- Deploy: GitHub → Laravel Forge. Queue driver `database`, cache `database` (Redis optional).

## How to read the design files
Each `design/<Screen>.dc.html` has two parts:
1. Template inside `<x-dc>…</x-dc>` — real HTML with **inline styles** (the source of truth for spacing/colours/sizes). `<sc-for list="{{ x }}" as="i">` = loop, `<sc-if value="{{ cond }}">` = conditional, `{{ var }}` = value.
2. `<script type="text/x-dc" data-dc-script>` — a JS class whose `renderVals()` returns every bound value, **dummy data arrays, dynamic style strings** (e.g. `rowStyle`, `badge()`, `chkBox()`, active/inactive tab styles), labels, and the behaviour of every button. Read it to know states, colours and business rules.
Screenshots of every screen (1440px) are in `design/screenshots/`. `design/uploads/*.pdf` are reference outputs (certificate, participant list, invoice).
Ignore `support.js` (prototype runtime) and all `localStorage` bridges — replace them with the database.

## Design tokens (Tailwind v4 `@theme` in resources/css/app.css)
primary #42481c · primary-hover #5a6127 · primary-soft #edeee0 · gold #C9A227 · gold-soft #FBF3DC · gold-ink #9a7d16
bg #F6F8F7 · surface #FFFFFF · border #E2E8F0 · divider #F1F5F4
ink #1A1D21 · ink-2 #334155 · ink-3 #475569 · muted #64748B · faint #94A3AC · checkbox-border #CBD5E1
success #16A34A/#EAF7EE · info #2563EB/#E8F0FB · warning #D97706/#FEF3E2 · danger #DC2626/#FDECEC · purple #7C3AED/#F3ECFB · neutral #64748B/#F1F5F4
Font Inter 400–800 (Amiri for Arabic/Jawi). Radius: button/input 9px, card 12px, modal 14px, badge 6px, pill 20px, checkbox 6px (20×20).
Modal: overlay rgba(20,24,20,.55), shadow 0 24px 60px rgba(0,0,0,.3). Layout: sidebar 260px, header 72px sticky, main padding 28px 32px 56px.
Table header: bg #F6F8F7, 11px/700 uppercase, letter-spacing .4px, colour #94A3AC; rows 13px, border-bottom #F1F5F4.
Use exact pixel values from the design (Tailwind arbitrary values like `text-[13.5px]`, `rounded-[9px]` are fine) — do not "round" to the default scale.

## Conventions
- Reusable Blade components in `resources/views/components/ui/*`: button, card, stat-card, kpi-card, badge, status-badge, checkbox, tabs (pill tabs with count), filter-select, search-input, modal (desktop centred / mobile bottom-sheet), drawer, data-table (desktop grid → mobile stacked cards), stepper (vertical workflow), page-header (breadcrumb + h1 + actions), empty-state, bulk-bar, avatar, doc-a4 / doc-a5 (printable preview). Build these first; pages compose them.
- Layouts: `layouts/app.blade.php` (sidebar + header), `layouts/auth.blade.php`, `layouts/public.blade.php` (tracking & portal).
- Livewire components under `app/Livewire/<Module>/…`, one per screen/view; modals as child components or Alpine state. Use `#[Url]` for filters/tabs so state survives refresh.
- Business logic in `app/Actions/*` (e.g. `VerifyPayment`, `RecordAkad`, `AssignAllocation`, `VerifyExecutionReport`, `GenerateAwb`) — never inside Blade. Every stage transition runs in a DB transaction, writes `order_stage_histories`, activity log, and fires an event.
- Enums in `app/Enums` (Service, Animal, OrderStatus, OrderStage, PoStatus, …) with `label()` and `badgeClasses()` returning the exact design colours.
- Money stored as integer sen (`unsigned bigInteger`), formatted via a helper `rm($sen)` → "RM 2,450"; compact `rm_short()` → "RM 3.82j" / "RM 168k".
- Dates via Carbon locale `ms`, format `d M Y` → "12 Jun 2027" (Malay month names: Jan Feb Mac Apr Mei Jun Jul Ogos Sep Okt Nov Dis).
- Authorization: permission per module `{module}.view` / `{module}.manage` (matrix Penuh=both, Lihat=view). Enforce in Policies/`authorize()` in every Livewire action, not only by hiding buttons.
- PDFs: Blade views in `resources/views/pdf/*` rendered with spatie/laravel-pdf; heavy/bulk ones queued. QR codes generated server-side as SVG.
- File uploads: private disk, signed temporary URLs, validate mime & size.
- Never commit `.env`, secrets or real customer data. Seeders use the design's dummy data (it is realistic and matches the screenshots).

## Responsive rules (mandatory on every screen)
Desktop ≥1024px = pixel-match design. <1024: sidebar becomes off-canvas drawer (hamburger in header), search becomes icon → full-screen overlay. <768: stat grids 2 columns, list tables become stacked cards (very wide tables = horizontal scroll with sticky first column), filters move into a "Tapis" bottom sheet, modals become full-screen sheets with sticky footer, bulk-action bar sticks to the bottom, tabs scroll horizontally, primary action full-width. Touch targets ≥44px, inputs ≥16px font on phones, no horizontal page scroll. See PRD §8.

## Verification loop (do this for every screen before saying it's done)
1. `php artisan test` green; `vendor/bin/pint --test`; `vendor/bin/phpstan`.
2. Pest browser test visits the page at 1440×900 and 390×844, asserts no JS errors and no horizontal overflow (`document.documentElement.scrollWidth <= innerWidth`).
3. Screenshot at 1440 and compare side-by-side with `design/screenshots/<Screen>_d.png`; fix differences.

## Commands
- `composer run dev` (serve + queue + vite), `php artisan migrate:fresh --seed`, `php artisan test`, `npm run build`.
- Local Windows env: see `docs/SETUP-DEV.md` (PHP 8.4 in `C:\php84`, MySQL 8.4 on **port 3307** started with `scripts\mysql-start.cmd`; XAMPP MariaDB stays on 3306).
- Design-system review pages (local only): `/_design/components`, `/_design/auth`, `/_design/public`. Sidebar menu lives in `config/navigation.php`; tone colour pairs in `App\Support\Tone`.

## Git
Conventional commits (`feat(orders): …`). One PR/commit per phase step. Branch `develop` → merge to `main` deploys to Forge.
