<?php

use App\Enums\RoleName;

it('user can log in with the password a Super Admin just set', function () {
    $admin = superAdmin();
    $user = userWithRoles(RoleName::Sales);
    $user->update(['name' => 'Rahim Salleh']);

    $this->actingAs($admin);

    visit('/pengguna')
        ->resize(...DESKTOP)
        ->click('[aria-label="Edit Rahim Salleh"]')
        ->wait(0.6)
        ->type('#new-password', 'BaruNq2026x')
        ->press('Simpan')
        ->wait(1)
        ->assertSee('telah ditetapkan');

    auth()->logout();

    visit('/login')
        ->resize(...DESKTOP)
        ->type('#a-email', $user->email)
        ->type('#a-password', 'BaruNq2026x')
        ->press('Log Masuk')
        ->wait(1.5)
        ->screenshot(filename: 'after-set-password-login')
        ->assertPathIs('/dashboard');
});
