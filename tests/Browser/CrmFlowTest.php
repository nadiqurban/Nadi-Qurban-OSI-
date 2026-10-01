<?php

use App\Enums\LeadStage;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DemoCrmSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoCrmSeeder::class]);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
});

it('shows the kanban like the design and drags a card', function () {
    $this->actingAs($this->admin);
    $lead = Lead::where('name', 'Masjid Al-Hidayah')->firstOrFail();

    $page = visit('/crm')
        ->resize(...DESKTOP)
        ->assertSee('Saluran Jualan')
        ->assertSee('Masjid Al-Hidayah')
        ->screenshot(filename: 'crm-kanban')
        // SortableJS is attached to every column (Playwright's single-step drag cannot drive its fallback mode)…
        ->assertScript("[...document.querySelectorAll('[data-stage] [x-data]')].every(el => Object.keys(el).some(k => k.startsWith('Sortable')))", true);

    // …and the drop handler's Livewire call persists the move.
    $page->script("(() => { let el = document.querySelector('[data-stage]'); while (el && ! el.hasAttribute('wire:id')) el = el.parentElement; Livewire.find(el.getAttribute('wire:id')).moveLead({$lead->id}, 'dihubungi', [{$lead->id}]); })()");

    $page->wait(1)
        ->assertSee('Masjid Al-Hidayah')
        ->assertNoJavaScriptErrors();

    expect($lead->fresh()->stage)->toBe(LeadStage::Contacted);

    $page->click('#mode-senarai')->wait(0.5)->assertSee('Peringkat')->screenshot(filename: 'crm-list');
});

it('shows the lead detail', function () {
    $this->actingAs($this->admin);
    $lead = Lead::where('name', 'Mohd Firdaus bin Omar')->firstOrFail();

    visit('/crm/'.$lead->id)
        ->resize(...DESKTOP)
        ->assertSee('Kemajuan Peringkat')
        ->assertSee('Aktiviti & Nota')
        ->screenshot(filename: 'crm-detail')
        ->assertNoJavaScriptErrors();
});

it('fits a phone without horizontal page overflow', function () {
    $this->actingAs($this->admin);

    visit('/crm')
        ->resize(...PHONE)
        ->assertSee('Saluran Jualan')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'crm-phone')
        ->assertNoJavaScriptErrors();
});
