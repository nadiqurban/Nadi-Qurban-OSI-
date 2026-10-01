<?php

use App\Models\InstallmentPlan;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/*
| Phase 10 responsive audit: Super Admin visits EVERY screen at phone (390×844),
| tablet (820×1180) and desktop (1440×900). Each page must load without console
| errors and without horizontal page overflow. Screenshots are copied to
| storage/app/qa/<viewport>/ for the visual-parity review.
*/

const QA_VIEWPORTS = ['phone' => [390, 844], 'tablet' => [820, 1180], 'desktop' => [1440, 900]];

beforeEach(function () {
    Storage::fake('local');
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
});

/** @return array<string, string> slug => path */
function qaPages(): array
{
    return [
        'dashboard' => '/dashboard',
        'ansuran' => '/ansuran',
        'tempahan' => '/tempahan',
        'tempahan-detail' => '/tempahan/'.Order::query()->latest('id')->value('id'),
        'pengesahan-bayaran' => '/pengesahan-bayaran',
        'lafaz-akad' => '/lafaz-akad',
        'agihan-negara' => '/agihan-negara',
        'pelaksanaan' => '/pelaksanaan',
        'awb' => '/awb',
        'tempahan-selesai' => '/tempahan-selesai',
        'vendor' => '/vendor',
        'vendor-profil' => '/vendor/'.Vendor::query()->value('id'),
        'produk' => '/produk',
        'dokumen' => '/dokumen',
        'crm' => '/crm',
        'crm-lead' => '/crm/'.Lead::query()->value('id'),
        'kod-promosi' => '/kod-promosi',
        'kewangan' => '/kewangan',
        'kewangan-invois' => '/kewangan/invois/'.Invoice::query()->value('id'),
        'laporan' => '/laporan',
        'audit-log' => '/audit-log',
        'notifikasi' => '/notifikasi',
        'pengguna' => '/pengguna',
        'pengguna-peranan' => '/pengguna/peranan/'.Role::query()->value('id'),
        'sijil' => '/sijil',
        'tetapan-profil' => '/tetapan/profil',
        'tetapan-keselamatan' => '/tetapan/keselamatan',
        'tetapan-syarikat' => '/tetapan/syarikat',
        'tetapan-integrasi' => '/tetapan/integrasi',
        'tetapan-webhooks' => '/tetapan/webhooks',
        'sejarah-log-masuk' => '/sejarah-log-masuk',
    ];
}

function qaKeep(string $viewport, string $slug): void
{
    $from = base_path("tests/Browser/Screenshots/qa-{$viewport}-{$slug}.png");

    if (is_file($from)) {
        File::ensureDirectoryExists(storage_path("app/qa/{$viewport}"));
        File::copy($from, storage_path("app/qa/{$viewport}/{$slug}.png"));
    }
}

it('renders every app screen cleanly', function (string $viewport) {
    $this->actingAs($this->admin);
    [$w, $h] = QA_VIEWPORTS[$viewport];

    foreach (qaPages() as $slug => $path) {
        visit($path)
            ->resize($w, $h)
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
            ->screenshot(fullPage: $viewport !== 'desktop', filename: "qa-{$viewport}-{$slug}")
            ->assertNoJavaScriptErrors();

        qaKeep($viewport, $slug);
    }
})->with(array_keys(QA_VIEWPORTS));

it('renders the public pages cleanly', function (string $viewport) {
    [$w, $h] = QA_VIEWPORTS[$viewport];
    $plan = InstallmentPlan::query()->firstOrFail();
    $order = Order::query()->whereNotIn('status', ['draf', 'dibatalkan'])->firstOrFail();

    foreach (['login' => '/login', 'jejak' => '/jejak?track='.$order->tracking_no, 'bayar' => '/bayar/'.$plan->pay_token] as $slug => $path) {
        visit($path)
            ->resize($w, $h)
            ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
            ->screenshot(fullPage: true, filename: "qa-{$viewport}-{$slug}")
            ->assertNoJavaScriptErrors();

        qaKeep($viewport, $slug);
    }
})->with(array_keys(QA_VIEWPORTS));

it('uses the off-canvas drawer below 1024 and full-screen sheets on phones', function () {
    $this->actingAs($this->admin);

    visit('/tempahan')
        ->resize(820, 1180)
        ->assertScript("getComputedStyle(document.querySelector('aside')).position === 'fixed' || document.querySelector('aside').getBoundingClientRect().right <= 0", true)
        ->click('[aria-label="Buka menu"]')
        ->wait(0.4)
        ->assertScript("document.querySelector('aside').getBoundingClientRect().left >= 0", true);

    visit('/kewangan')
        ->resize(390, 844)
        ->click('#btn-invoice')
        ->wait(0.6)
        // Modal panel spans the whole viewport width on phones (bottom/full sheet).
        ->assertScript("(() => { const d = [...document.querySelectorAll('[role=dialog]')].find(e => getComputedStyle(e).display !== 'none' && e.querySelector('h2')); return !!d && Math.round(d.querySelector('h2').closest('.flex.w-full').getBoundingClientRect().width) === window.innerWidth; })()", true)
        ->assertNoJavaScriptErrors();
});
