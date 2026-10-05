<?php

use App\Enums\RoleName;
use Illuminate\Support\Facades\Hash;

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

it('lets a Super Admin set a password in Edit Pengguna, then the user logs in directly', function () {
    $this->actingAs(superAdmin());
    $user = userWithRoles(RoleName::Sales);
    $user->update(['name' => 'Rahim Salleh']);

    visit('/pengguna')
        ->resize(...DESKTOP)
        ->click('[aria-label="Edit Rahim Salleh"]')
        ->wait(0.6)
        ->assertSee('Kata Laluan Baharu')
        ->type('#new-password', 'BaruNq2026x')
        ->screenshot(filename: 'users-edit-password')
        ->press('Simpan')
        ->wait(1)
        ->assertSee('telah ditetapkan')
        ->assertNoJavaScriptErrors();

    expect(Hash::check('BaruNq2026x', $user->fresh()->password))->toBeTrue();
});

it('shows the new-password slot in the Edit Pengguna sheet on phones', function () {
    $this->actingAs(superAdmin());
    userWithRoles(RoleName::Sales)->update(['name' => 'Rahim Salleh']);

    visit('/pengguna')
        ->resize(...PHONE)
        ->click('[aria-label="Edit Rahim Salleh"]')
        ->wait(0.6)
        ->assertSee('Kata Laluan Baharu')
        ->screenshot(filename: 'users-edit-password-phone')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});

it('shows the delete button in the users table on desktop and phone', function () {
    $this->actingAs(superAdmin());
    userWithRoles(RoleName::Sales)->update(['name' => 'Rahim Salleh']);

    visit('/pengguna')
        ->resize(...DESKTOP)
        ->assertPresent('[aria-label="Padam Rahim Salleh"]')
        ->screenshot(filename: 'users-delete-button')
        ->resize(...PHONE)
        ->assertPresent('[aria-label="Padam Rahim Salleh"]')
        ->screenshot(filename: 'users-delete-button-phone')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();
});
