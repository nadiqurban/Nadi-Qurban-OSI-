<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Livewire\Agent\Login as AgentLogin;
use App\Livewire\Agents\Index as AgentsIndex;
use App\Livewire\Public\AgentRegister;
use App\Models\Agent;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class]);
});

function registerAgent(string $name = 'Siti Aminah binti Rahman', string $email = 'siti@example.com'): Agent
{
    Livewire::test(AgentRegister::class)
        ->set('name', $name)->set('email', $email)->set('phone', '012-555 7788')
        ->set('gender', 'Perempuan')->set('birthDate', '1995-04-12')->set('district', 'Petaling')->set('state', 'Selangor')
        ->set('photo', UploadedFile::fake()->image('wajah.png', 800, 600))
        ->set('bankName', 'Maybank')->set('bankAccountName', $name)->set('bankAccountNo', '5623 5782 2681')
        ->set('password', 'EjenBaru2027')->set('passwordConfirmation', 'EjenBaru2027')
        ->set('agreed', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Pendaftaran Anda Sedang Disahkan');

    return Agent::query()->whereHas('user', fn ($q) => $q->where('email', $email))->with('user')->firstOrFail();
}

it('shows the public Pendaftaran Ejen form', function () {
    $this->get('/daftar-ejen')->assertOk()
        ->assertSee('Pendaftaran Ejen')->assertSee('Gambar Terkini')->assertSee('Hantar Pendaftaran');
});

it('registers an agent as Menunggu with a generated code and a 320px photo', function () {
    $agent = registerAgent();

    expect($agent->isPending())->toBeTrue()
        ->and($agent->code)->toBe('SA01')
        ->and($agent->user->status)->toBe(UserStatus::Suspended)
        ->and($agent->user->hasRole(RoleName::Agent->value))->toBeTrue()
        ->and($agent->photo())->not->toBeNull()
        ->and(getimagesize($agent->photo()->getPath())[0])->toBe(320)
        ->and(Activity::where('event', 'agent.registered')->exists())->toBeTrue();

    expect(registerAgent('Ahmad Hafiz', 'hafiz@example.com')->code)->toBe('AH02');
});

it('validates the registration form in Malay', function () {
    registerAgent();

    Livewire::test(AgentRegister::class)
        ->set('name', 'Lain')->set('email', 'siti@example.com')->set('phone', '012-555 7788')
        ->set('password', 'EjenBaru2027')->set('passwordConfirmation', 'Lain2027x')
        ->call('submit')
        ->assertHasErrors(['email', 'photo', 'passwordConfirmation', 'agreed'])
        ->assertSee('Emel ini sudah berdaftar.');
});

it('keeps a pending agent out of the portal until HQ approves', function () {
    $agent = registerAgent();

    Livewire::test(AgentLogin::class)
        ->set('email', 'siti@example.com')->set('password', 'EjenBaru2027')
        ->call('login')
        ->assertHasErrors('email')
        ->assertSee('Pendaftaran anda sedang disahkan');

    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)
        ->assertSee('Pendaftaran Baharu')->assertSee('Menunggu')
        ->assertSee('Portal Ejen')->assertSeeHtml('href="'.route('agent.login').'"')
        ->call('togglePending')->assertSee('Siti Aminah binti Rahman')
        ->call('approve', $agent->id)
        ->assertSee('Aktif');

    expect($agent->fresh()->isActive())->toBeTrue()
        ->and(Activity::where('event', 'agent.approved')->exists())->toBeTrue();

    app('auth')->forgetGuards();
    Livewire::test(AgentLogin::class)
        ->set('email', 'siti@example.com')->set('password', 'EjenBaru2027')
        ->call('login')
        ->assertRedirect(route('agent.portal'));
});

it('rejects a registration and tells the agent on login', function () {
    $agent = registerAgent();

    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)->call('reject', $agent->id)->assertSee('Ditolak');

    expect($agent->fresh()->isRejected())->toBeTrue();

    app('auth')->forgetGuards();
    Livewire::test(AgentLogin::class)
        ->set('email', 'siti@example.com')->set('password', 'EjenBaru2027')
        ->call('login')
        ->assertSee('tidak diluluskan');
});

it('serves the agent photo to HQ only and needs manage permission to approve', function () {
    $agent = registerAgent();

    $this->get(route('agents.photo', $agent))->assertRedirect();
    $this->actingAs(superAdmin())->get(route('agents.photo', $agent))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get(route('agents.photo', ['agent' => $agent, 'muat-turun' => 1]))
        ->assertHeader('Content-Disposition', 'attachment; filename="Gambar-SA01.jpg"');

    Livewire::actingAs(userWithRoles(RoleName::Sales))->test(AgentsIndex::class)
        ->call('approve', $agent->id)->assertForbidden();
});
