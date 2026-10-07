<?php

it('redirects the root to the first module', function () {
    $this->actingAs(superAdmin())->get('/')->assertRedirect(route('dashboard'));
});

it('renders every sidebar route inside the app shell for a Super Admin', function () {
    $routes = collect(config('navigation'))->flatMap(fn (array $group) => $group['items'])->pluck('route');

    expect($routes)->toHaveCount(22);

    $this->actingAs($admin = superAdmin());

    foreach ($routes as $name) {
        $this->get(route($name))
            ->assertOk()
            ->assertSee('NADI QURBAN')
            ->assertSee($admin->name)
            ->assertSee('Cari tempahan, pelanggan, vendor, no. tracking', false);
    }
});

it('renders sidebar sections and labels in design order', function () {
    $this->actingAs(superAdmin())->get('/dashboard')->assertSeeInOrder([
        'UTAMA', 'Dashboard',
        'OPERASI', 'Bayaran Ansuran', 'Tempahan &amp; Pelanggan', 'Pengesahan Bayaran', 'Lafaz Akad', 'Agihan Negara',
        'Pelaksanaan &amp; Laporan', 'AWB &amp; Postage', 'Tempahan Selesai', 'Vendor', 'Produk', 'Dokumen',
        'JUALAN &amp; KEWANGAN', 'Sales CRM', 'Kod Promosi', 'Kewangan',
        'LAPORAN', 'Pusat Laporan', 'Audit Log',
        'SISTEM', 'Notifikasi', 'Pengguna', 'Sijil', 'Tetapan',
    ], false);
});

it('marks the current module as active', function () {
    $this->actingAs(superAdmin())->get('/produk')->assertSee('aria-current="page"', false);
});

it('serves the design system pages outside production', function (string $uri) {
    $this->actingAs(superAdmin())->get($uri)->assertOk();
})->with(['/_design/components', '/_design/auth', '/_design/public']);
