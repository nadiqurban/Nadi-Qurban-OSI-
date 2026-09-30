<?php

use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\MasterDataSeeder;

/*
| Verification loop (CLAUDE.md §2): each page at 1440×900 and 390×844,
| no JS errors and no horizontal page overflow.
*/

$appPages = [
    '/dashboard', '/tempahan', '/pengguna', '/pengguna?tab=peranan', '/produk', '/kod-promosi',
    '/tetapan/profil', '/tetapan/syarikat', '/tetapan/keselamatan', '/sejarah-log-masuk',
    '/_design/components',
];

$guestPages = ['/login', '/lupa-kata-laluan', '/_design/public'];

beforeEach(fn () => $this->seed([MasterDataSeeder::class, DemoCatalogSeeder::class]));

it('has no JS errors or horizontal overflow on desktop', function (string $uri) {
    $this->actingAs(superAdmin());

    visit($uri)
        ->resize(...DESKTOP)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with($appPages);

it('has no JS errors or horizontal overflow on phones', function (string $uri) {
    $this->actingAs(superAdmin());

    visit($uri)
        ->resize(...PHONE)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with($appPages);

it('renders guest pages without errors or overflow', function (string $uri, array $size) {
    visit($uri)
        ->resize(...$size)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with($guestPages)->with([[DESKTOP], [PHONE]]);

it('opens the sidebar drawer from the hamburger on phones', function () {
    $this->actingAs(superAdmin());

    visit('/dashboard')
        ->resize(...PHONE)
        ->assertScript('document.querySelector("aside").getBoundingClientRect().right <= 0', true)
        ->click('[aria-label="Buka menu"]')
        ->wait(0.4)
        ->assertScript('document.querySelector("aside").getBoundingClientRect().left >= 0', true)
        ->assertSee('Pengesahan Bayaran');
});

it('shows the fixed sidebar on desktop', function () {
    $this->actingAs(superAdmin());

    visit('/dashboard')
        ->resize(...DESKTOP)
        ->assertScript('document.querySelector("aside").getBoundingClientRect().width', 260)
        ->assertScript('document.querySelector("header").getBoundingClientRect().height', 72);
});

it('hides the login brand panel on phones', function () {
    visit('/login')
        ->resize(...PHONE)
        ->assertSee('Log Masuk Akaun')
        ->assertScript('getComputedStyle(document.querySelector(".bg-primary.text-white")).display', 'none');
});
