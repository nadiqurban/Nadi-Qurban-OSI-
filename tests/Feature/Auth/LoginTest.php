<?php

use App\Enums\LoginStatus;
use App\Enums\RoleName;
use App\Livewire\Auth\Login;
use App\Models\LoginHistory;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('shows the login screen to guests and redirects them from the app', function () {
    $this->get('/login')->assertOk()->assertSee('Log Masuk Akaun')->assertSee('SELAMAT KEMBALI');
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/')->assertRedirect('/login');
});

it('logs in with valid credentials and records history + audit', function () {
    $user = userWithRoles(RoleName::AdminHq);

    Livewire::test(Login::class)
        ->set('email', strtoupper($user->email))
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect(LoginHistory::where('user_id', $user->id)->where('status', LoginStatus::Success)->count())->toBe(1)
        ->and(Activity::where('event', 'login')->where('causer_id', $user->id)->exists())->toBeTrue();
});

it('rejects a wrong password without revealing whether the email exists', function () {
    $user = userWithRoles(RoleName::Sales);

    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'salah')->call('login')
        ->assertHasErrors(['email' => 'Emel atau kata laluan tidak sah.']);

    Livewire::test(Login::class)->set('email', 'tiada@nadiqurban.com')->set('password', 'salah')->call('login')
        ->assertHasErrors(['email' => 'Emel atau kata laluan tidak sah.']);

    $this->assertGuest();
    expect($user->fresh()->failed_login_attempts)->toBe(1);
});

it('locks the account for 15 minutes after 5 failed attempts', function () {
    $user = userWithRoles(RoleName::Sales);

    foreach (range(1, 4) as $i) {
        Livewire::test(Login::class)->set('email', $user->email)->set('password', 'salah')->call('login')->assertHasErrors('email');
    }

    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'salah')->call('login')
        ->assertRedirect(route('login.locked'));

    $user->refresh();
    expect($user->isLocked())->toBeTrue()
        ->and($user->locked_until->diffInMinutes(now(), true))->toBeGreaterThan(14.0);

    // Even the correct password is refused while locked.
    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'password')->call('login')
        ->assertRedirect(route('login.locked'));
    $this->assertGuest();

    expect(Activity::where('event', 'login.locked')->value('severity'))->toBe('kritikal');

    // After 15 minutes the account can log in again.
    $this->travel(16)->minutes();
    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'password')->call('login')
        ->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});

it('shows a live countdown on the locked screen', function () {
    $this->withSession(['login.locked_until' => now()->addMinutes(14)->addSeconds(52)->timestamp])
        ->get('/akaun-dikunci')
        ->assertOk()
        ->assertSee('Akaun Dikunci')
        ->assertSee('14:52');
});

it('refuses suspended users', function () {
    $user = User::factory()->suspended()->create();

    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'password')->call('login')
        ->assertHasErrors(['email' => 'Akaun anda telah digantung. Sila hubungi pentadbir sistem.']);

    $this->assertGuest();
});

it('logs out a user who is suspended mid-session', function () {
    $user = userWithRoles(RoleName::AdminHq);
    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->forceFill(['status' => 'digantung'])->save();

    $this->get('/dashboard')->assertRedirect('/login');
    $this->assertGuest();
});

it('remembers only the email with "Ingat saya" — no persistent login cookie', function () {
    $user = userWithRoles(RoleName::AdminHq);

    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'password')->set('remember', true)->call('login');

    expect(auth()->viaRemember())->toBeFalse()
        ->and($user->fresh()->remember_token)->toBe($user->remember_token);
});

it('logs out and records it', function () {
    $user = userWithRoles(RoleName::AdminHq);

    $this->actingAs($user)->post('/log-keluar')->assertRedirect('/login');
    $this->assertGuest();

    $this->actingAs($user)->post('/log-keluar', ['idle' => 1])
        ->assertSessionHas('warning', 'Sesi anda tamat selepas 30 minit tidak aktif. Sila log masuk semula.');
});

it('expires sessions after 30 idle minutes and ships the idle timer', function () {
    expect(config('session.lifetime'))->toBe(30);

    $this->actingAs(superAdmin())->get('/dashboard')
        ->assertSee('idleLogout(30)', false);
});
