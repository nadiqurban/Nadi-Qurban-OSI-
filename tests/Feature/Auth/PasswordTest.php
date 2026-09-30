<?php

use App\Enums\RoleName;
use App\Livewire\Auth\ForceChangePassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

it('sends a reset link and shows the same message for unknown emails', function () {
    Notification::fake();
    $user = User::factory()->create();

    Livewire::test(ForgotPassword::class)->set('email', $user->email)->call('send')->assertSet('sent', true);
    Livewire::test(ForgotPassword::class)->set('email', 'tiada@nadiqurban.com')->call('send')->assertSet('sent', true);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
    Notification::assertCount(1);
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['must_change_password' => true]);
    $token = Password::createToken($user);

    Livewire::withQueryParams(['email' => $user->email])
        ->test(ResetPassword::class, ['token' => $token])
        ->set('password', 'BaharuKuat9!')
        ->set('password_confirmation', 'BaharuKuat9!')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertSet('done', true);

    $user->refresh();
    expect(Hash::check('BaharuKuat9!', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse();
});

it('enforces the password policy (8 chars, upper, lower, number)', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::withQueryParams(['email' => $user->email])
        ->test(ResetPassword::class, ['token' => $token])
        ->set('password', 'lemah')
        ->set('password_confirmation', 'lemah')
        ->call('resetPassword')
        ->assertHasErrors('password');
});

it('forces a password change before any other page', function () {
    $user = User::factory()->mustChangePassword()->create();
    $user->assignRole(RoleName::AdminHq->value);

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.force'));
    $this->actingAs($user)->get('/pengguna')->assertRedirect(route('password.force'));
    $this->actingAs($user)->get('/tukar-kata-laluan')->assertOk()->assertSee('Tukar Kata Laluan Diperlukan');

    Livewire::actingAs($user)->test(ForceChangePassword::class)
        ->set('current_password', 'salah')
        ->set('password', 'BaharuKuat9!')
        ->set('password_confirmation', 'BaharuKuat9!')
        ->call('change')
        ->assertHasErrors('current_password');

    Livewire::actingAs($user)->test(ForceChangePassword::class)
        ->set('current_password', 'password')
        ->set('password', 'BaharuKuat9!')
        ->set('password_confirmation', 'BaharuKuat9!')
        ->call('change')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    expect($user->fresh()->must_change_password)->toBeFalse();
    $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
});
