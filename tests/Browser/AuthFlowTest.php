<?php

use App\Enums\RoleName;

it('logs in through the real form and lands on the dashboard', function () {
    $user = userWithRoles(RoleName::AdminHq);

    visit('/login')
        ->resize(...DESKTOP)
        ->type('#a-email', $user->email)
        ->type('#a-password', 'password')
        ->press('Log Masuk')
        ->wait(1)
        ->assertPathIs('/dashboard')
        ->assertSee($user->name)
        ->assertNoJavaScriptErrors();
});

it('shows an error for a wrong password', function () {
    $user = userWithRoles(RoleName::Sales);

    visit('/login')
        ->type('#a-email', $user->email)
        ->type('#a-password', 'salah-sekali')
        ->press('Log Masuk')
        ->wait(1)
        ->assertPathIs('/login')
        ->assertSee('Emel atau kata laluan tidak sah.');
});

it('opens the Tetapan modal from the sidebar', function () {
    $this->actingAs(superAdmin());

    visit('/dashboard')
        ->resize(...DESKTOP)
        ->click('Tetapan')
        ->wait(0.4)
        ->assertSee('Konfigurasi sistem Nadi Qurban OSI')
        ->assertSee('Maklumat Syarikat')
        ->assertNoJavaScriptErrors();
});

it('opens the add-user modal as a full-screen sheet on phones', function () {
    $this->actingAs(superAdmin());

    visit('/pengguna')
        ->resize(...PHONE)
        ->press('Tambah Pengguna')
        ->wait(0.6)
        ->assertSee('Cipta akaun pengguna baharu')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});
