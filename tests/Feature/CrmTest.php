<?php

use App\Enums\LeadStage;
use App\Enums\RoleName;
use App\Events\LeadStageChanged;
use App\Livewire\Crm\Pipeline;
use App\Livewire\Crm\Show;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Models\Lead;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoCrmSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoCrmSeeder::class]);
    $this->sales = userWithRoles(RoleName::Sales);
});

it('shows the pipeline with KPIs, kanban and list view', function () {
    $this->actingAs($this->sales)->get('/crm')
        ->assertOk()
        ->assertSee('Saluran Jualan')
        ->assertSee('Lead Aktif')
        ->assertSee('Masjid Al-Hidayah')
        ->assertSee('Rundingan');

    Livewire::actingAs($this->sales)->test(Pipeline::class)
        ->set('mode', 'senarai')
        ->assertSee('Peringkat')
        ->assertSee('Yayasan Ikhlas');
});

it('moves a lead between columns and persists stage + order', function () {
    Event::fake([LeadStageChanged::class]);
    $lead = Lead::query()->where('name', 'Ahmad Zaki bin Hassan')->firstOrFail();
    $first = Lead::query()->where('stage', LeadStage::Contacted)->orderBy('position')->firstOrFail();

    Livewire::actingAs($this->sales)->test(Pipeline::class)
        ->call('moveLead', $lead->id, 'dihubungi', [$lead->id, $first->id]);

    expect($lead->fresh()->stage)->toBe(LeadStage::Contacted)
        ->and($lead->fresh()->position)->toBe(0)
        ->and($first->fresh()->position)->toBe(1)
        ->and($lead->activities()->where('type', 'stage')->exists())->toBeTrue();
    Event::assertDispatched(LeadStageChanged::class);
});

it('creates a lead from the modal', function () {
    Livewire::actingAs($this->sales)->test(Pipeline::class)
        ->call('openNewLead')
        ->set('name', 'Surau Al-Falah')
        ->set('service', 'Korporat')
        ->set('value', '12,500')
        ->call('saveLead')
        ->assertHasNoErrors()
        ->assertSee('Surau Al-Falah');

    $lead = Lead::query()->where('name', 'Surau Al-Falah')->firstOrFail();
    expect($lead->lead_no)->toBe('LEAD-5051')->and($lead->value_sen)->toBe(1_250_000)->and($lead->stage)->toBe(LeadStage::New);
});

it('blocks lead changes for view-only roles', function () {
    $finance = userWithRoles(RoleName::Finance); // CRM = Lihat
    $lead = Lead::query()->firstOrFail();

    Livewire::actingAs($finance)->test(Pipeline::class)
        ->call('moveLead', $lead->id, 'ditutup', [$lead->id])
        ->assertForbidden();

    $this->actingAs(userWithRoles(RoleName::Operations))->get('/crm')->assertForbidden();
});

it('autosaves notes, adds activities and marks the lead done', function () {
    $lead = Lead::query()->where('name', 'Surau An-Nur')->firstOrFail();

    Livewire::actingAs($this->sales)->test(Show::class, ['lead' => $lead])
        ->assertSee('Kemajuan Peringkat')
        ->set('leadNotes', 'Minta harga korporat.')
        ->call('toggleNote')
        ->set('noteText', 'Hubungi semula Isnin.')
        ->call('saveNote')
        ->assertSee('Hubungi semula Isnin.')
        ->call('toggleDone')
        ->assertSee('Lead Selesai');

    $lead->refresh();
    expect($lead->notes)->toBe('Minta harga korporat.')
        ->and($lead->stage)->toBe(LeadStage::Closed)
        ->and($lead->done_at)->not->toBeNull();
});

it('converts a closed lead into a prefilled order', function () {
    $this->seed(DemoCatalogSeeder::class);
    $admin = superAdmin();
    $lead = Lead::query()->where('name', 'Zulhilmi bin Abdullah')->firstOrFail();

    Livewire::actingAs($admin)->test(Show::class, ['lead' => $lead])
        ->assertSee('Tukar ke Tempahan')
        ->call('convert')
        ->assertRedirect(route('orders.index', ['lead' => $lead->id]));

    $this->actingAs($admin);
    Livewire::withQueryParams(['lead' => $lead->id])->test(OrdersIndex::class)
        ->assertSet('showForm', true)
        ->assertSet('form.name', 'Zulhilmi bin Abdullah')
        ->assertSet('fromLead', $lead->id);
});
