<?php

/*
| Verification loop (CLAUDE.md §2): each page at 1440×900 and 390×844,
| no JS errors and no horizontal page overflow.
*/

$pages = ['/dashboard', '/tempahan', '/_design/components', '/_design/auth', '/_design/public'];

it('has no JS errors or horizontal overflow on desktop', function (string $uri) {
    visit($uri)
        ->resize(...DESKTOP)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with($pages);

it('has no JS errors or horizontal overflow on phones', function (string $uri) {
    visit($uri)
        ->resize(...PHONE)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with($pages);

it('opens the sidebar drawer from the hamburger on phones', function () {
    visit('/dashboard')
        ->resize(...PHONE)
        ->assertScript('document.querySelector("aside").getBoundingClientRect().right <= 0', true)
        ->click('[aria-label="Buka menu"]')
        ->wait(0.4)
        ->assertScript('document.querySelector("aside").getBoundingClientRect().left >= 0', true)
        ->assertSee('Pengesahan Bayaran');
});

it('shows the fixed sidebar on desktop', function () {
    visit('/dashboard')
        ->resize(...DESKTOP)
        ->assertScript('document.querySelector("aside").getBoundingClientRect().width', 260)
        ->assertScript('document.querySelector("header").getBoundingClientRect().height', 72);
});
