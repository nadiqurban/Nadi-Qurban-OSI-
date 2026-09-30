<?php

use App\Enums\RoleName;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Settings\Company;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

it('lets every user edit their own profile and password', function () {
    $user = userWithRoles(RoleName::Sales);

    $this->actingAs($user)->get('/tetapan/profil')->assertOk()->assertSee('Profil &amp; Akaun', false);

    Livewire::actingAs($user)->test(Profile::class)
        ->set('name', 'Rahim Salleh')
        ->set('phone', '012-3456 789')
        ->set('current_password', 'password')
        ->set('password', 'BaharuKuat9!')
        ->set('password_confirmation', 'BaharuKuat9!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', 'Perubahan disimpan.');

    $user->refresh();
    expect($user->name)->toBe('Rahim Salleh')
        ->and(Hash::check('BaharuKuat9!', $user->password))->toBeTrue();
});

it('uploads a profile photo to the public disk', function () {
    Storage::fake('public');
    $user = userWithRoles(RoleName::Sales);

    Livewire::actingAs($user)->test(Profile::class)
        ->set('photo', UploadedFile::fake()->image('saya.png', 200, 200))
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists($user->fresh()->avatar_path);
});

it('saves company details used on documents', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->get('/tetapan/syarikat')->assertOk()->assertSee('Nadi Qurban Sdn. Bhd.')->assertSee('1677511-A');

    Livewire::actingAs($admin)->test(Company::class)
        ->set('company.phone', '03-2181 9999')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect(app(Settings::class)->get('company.phone'))->toBe('03-2181 9999');
});

it('enables and disables TOTP two-factor authentication', function () {
    $user = userWithRoles(RoleName::AdminHq);
    $google2fa = app(Google2FA::class);

    $component = Livewire::actingAs($user)->test(Security::class)->call('startTwoFactor');
    $secret = $component->get('pendingSecret');

    $component->set('code', '000000')->call('confirmTwoFactor')->assertHasErrors('code');
    $component->set('code', $google2fa->getCurrentOtp($secret))->call('confirmTwoFactor')->assertHasNoErrors();

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();

    Livewire::actingAs($user->fresh())->test(Security::class)
        ->set('password', 'password')
        ->call('disableTwoFactor')
        ->assertHasNoErrors();

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('asks for the 2FA code after the password when enabled', function () {
    $user = userWithRoles(RoleName::AdminHq);
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey(32);
    $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

    Livewire::test(Login::class)
        ->set('email', $user->email)->set('password', 'password')->call('login')
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    Livewire::test(TwoFactorChallenge::class)
        ->set('code', $google2fa->getCurrentOtp($secret))
        ->call('verify')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});
