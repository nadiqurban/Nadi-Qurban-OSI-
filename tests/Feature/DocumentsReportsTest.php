<?php

use App\Enums\DocumentCategory;
use App\Enums\ReportType;
use App\Enums\RoleName;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Models\Document;
use App\Models\ReportExport;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoFinanceSeeder::class]);
    $this->admin = superAdmin();
});

it('auto-registers invoices as documents and opens them', function () {
    expect(Document::query()->where('category', DocumentCategory::Invoice)->count())->toBe(12);

    $doc = Document::query()->where('name', 'Invois INV-2027-0891.pdf')->firstOrFail();
    expect($doc->route_name)->toBe('finance.invoice.pdf')->and($doc->source)->toBe('system');

    $this->actingAs($this->admin)->get(route('documents.open', $doc))
        ->assertRedirect(route('finance.invoice.pdf', ['invoice' => $doc->route_params['invoice']]));
});

it('lists, filters, uploads and deletes documents', function () {
    Livewire::actingAs($this->admin)->test(DocumentsIndex::class)
        ->assertSee('Repositori Dokumen')
        ->assertSee('Invois INV-2027-0891.pdf')
        ->set('category', 'perjanjian')
        ->assertDontSee('Invois INV-2027-0891.pdf')
        ->call('openUpload')
        ->set('files', [UploadedFile::fake()->create('Perjanjian Al-Barakah 2027.pdf', 300, 'application/pdf'), UploadedFile::fake()->image('Foto Agihan Lembu.jpg')])
        ->call('upload')
        ->assertHasNoErrors()
        ->assertSee('Perjanjian Al-Barakah 2027.pdf');

    $doc = Document::query()->where('name', 'Foto Agihan Lembu.jpg')->firstOrFail();
    expect($doc->service)->toBe('Lembu')->and($doc->media_id)->not->toBeNull()->and($doc->source)->toBe('upload');

    $this->actingAs($this->admin)->get(route('documents.open', $doc))->assertOk();

    Livewire::actingAs($this->admin)->test(DocumentsIndex::class)->call('delete', $doc->id);
    expect(Document::query()->find($doc->id))->toBeNull();
});

it('rejects disallowed uploads and view-only roles', function () {
    Livewire::actingAs($this->admin)->test(DocumentsIndex::class)
        ->call('openUpload')
        ->set('files', [UploadedFile::fake()->create('skrip.exe', 10)])
        ->call('upload')
        ->assertHasErrors('files.0');

    Livewire::actingAs(userWithRoles(RoleName::Finance))->test(DocumentsIndex::class) // Dokumen = Lihat
        ->call('openUpload')
        ->assertForbidden();
});

it('queues reports, registers them and downloads the file', function () {
    Livewire::actingAs($this->admin)->test(ReportsIndex::class)
        ->assertSee('Jana Laporan Pantas')
        ->set('type', 'kewangan')
        ->set('format', 'xlsx')
        ->set('from', now()->subMonths(3)->toDateString())
        ->call('generate')
        ->assertHasNoErrors()
        ->call('quick', 'peserta');

    $xlsx = ReportExport::query()->where('type', ReportType::Finance)->firstOrFail();
    $csv = ReportExport::query()->where('type', ReportType::Participants)->firstOrFail();

    expect($xlsx->status)->toBe(ReportExport::DONE)
        ->and($csv->format)->toBe('csv')
        ->and($csv->status)->toBe(ReportExport::DONE);
    Storage::disk('local')->assertExists($xlsx->path);
    expect(Document::query()->where('key', 'report:'.$xlsx->id)->exists())->toBeTrue()
        ->and($this->admin->fresh()->notifications()->count())->toBe(2);

    $this->actingAs($this->admin)->get(route('reports.download', $xlsx))->assertOk()->assertDownload($xlsx->file_name);
});

it('validates the report builder', function () {
    Livewire::actingAs($this->admin)->test(ReportsIndex::class)
        ->set('from', '2027-06-30')
        ->set('to', '2027-06-01')
        ->call('generate')
        ->assertHasErrors('to');

    expect(ReportExport::query()->count())->toBe(0);
});
