<?php

use App\Actions\Finance\Invoices;
use App\Enums\InvoiceStatus;
use App\Enums\RoleName;
use App\Events\InvoicePaid;
use App\Livewire\Finance\Index;
use App\Livewire\Finance\InvoiceShow;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\User;
use App\Models\VendorPayment;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoPurchaseOrderSeeder::class, DemoFinanceSeeder::class]);
    $this->finance = userWithRoles(RoleName::Finance);
});

it('shows KPIs, charts, invoices and completed PO payments', function () {
    $this->actingAs($this->finance)->get('/kewangan')
        ->assertOk()
        ->assertSee('Pengurusan Kewangan')
        ->assertSee('Aliran Tunai')
        ->assertSee('Kaedah Bayaran')
        ->assertSee('INV-2027-0891')
        ->assertSee('Ahmad Zaki bin Hassan')
        ->assertSee('Rekod PO — Payment Completed');
});

it('filters invoices by tab, status and search', function () {
    Livewire::actingAs($this->finance)->test(Index::class)
        ->set('tab', 'dibayar')
        ->assertSee('INV-2027-0890')
        ->assertDontSee('INV-2027-0889')
        ->set('tab', 'tertunggak')
        ->assertSee('INV-2027-0889')
        ->assertDontSee('INV-2027-0890')
        ->set('tab', 'semua')
        ->set('status', 'lewat')
        ->assertSee('Rosmah binti Idris')
        ->assertDontSee('Ahmad Zaki bin Hassan')
        ->set('status', '')
        ->set('search', 'Zulhilmi')
        ->assertSee('INV-2027-0887')
        ->assertDontSee('INV-2027-0891');
});

it('creates an invoice from the modal with company block and items', function () {
    Livewire::actingAs($this->finance)->test(Index::class)
        ->call('openDraft', 'invoice')
        ->assertSet('showDraft', true)
        ->assertSet('draft.company.ssm', '1677511-A')
        ->set('draft.name', 'Pelanggan Ujian')
        ->set('draft.order', 'NQ-QB-LE-009999')
        ->set('draft.items.0.price', '3,500')
        ->call('addItem')
        ->set('draft.items.1.description', 'Yuran Logistik')
        ->set('draft.items.1.qty', '2')
        ->set('draft.items.1.price', '150.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showDraft', false);

    $invoice = Invoice::query()->where('customer_name', 'Pelanggan Ujian')->firstOrFail();
    expect($invoice->invoice_no)->toBe('INV-2027-0892')
        ->and($invoice->total_sen)->toBe(350000 + 30100)
        ->and($invoice->status)->toBe(InvoiceStatus::Outstanding)
        ->and($invoice->items)->toHaveCount(2)
        ->and($invoice->company['name'])->not->toBe('');
});

it('validates the draft and blocks users without manage permission', function () {
    Livewire::actingAs($this->finance)->test(Index::class)
        ->call('openDraft', 'invoice')
        ->set('draft.name', '')
        ->set('draft.items.0.qty', '0')
        ->call('save')
        ->assertHasErrors(['draft.name', 'draft.items.0.qty']);

    $viewer = userWithRoles(RoleName::AdminHq); // Kewangan = Lihat
    Livewire::actingAs($viewer)->test(Index::class)
        ->call('openDraft', 'invoice')
        ->assertForbidden();

    $this->actingAs(userWithRoles(RoleName::Sales))->get('/kewangan')->assertForbidden();
});

it('creates a quotation and redirects to its PDF', function () {
    Livewire::actingAs($this->finance)->test(Index::class)
        ->call('openDraft', 'quote')
        ->assertSet('draft.type', 'quote')
        ->set('draft.name', 'Syarikat ABC')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $quote = Quotation::query()->firstOrFail();
    expect($quote->quotation_no)->toBe('QUO-2027-0076')->and($quote->total_sen)->toBe(350000);
});

it('records payments, derives statuses and fires InvoicePaid', function () {
    Event::fake([InvoicePaid::class]);
    $invoice = Invoice::query()->where('invoice_no', 'INV-2027-0889')->firstOrFail();

    Livewire::actingAs($this->finance)->test(InvoiceShow::class, ['invoice' => $invoice])
        ->assertSee('Bil Kepada')
        ->assertSee('Menunggu baki RM 5,200')
        ->call('openPay')
        ->set('payAmount', '2000')
        ->call('confirmPayment')
        ->assertHasNoErrors();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Deposit);
    Event::assertNotDispatched(InvoicePaid::class);

    expect(fn () => app(Invoices::class)->recordPayment($invoice->fresh(), 999_999_00, 'Tunai', null, now(), $this->finance))
        ->toThrow(ValidationException::class);

    Livewire::actingAs($this->finance)->test(InvoiceShow::class, ['invoice' => $invoice->fresh()])
        ->call('openPay')
        ->assertSet('payAmount', '3200.00')
        ->call('confirmPayment');

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)->and($invoice->fresh()->paid_sen)->toBe(520000);
    Event::assertDispatched(InvoicePaid::class);
});

it('marks overdue invoices as Lewat in the daily job', function () {
    $invoice = Invoice::query()->where('invoice_no', 'INV-2027-0887')->firstOrFail();
    $invoice->update(['due_date' => today()->subDay()]);

    $this->artisan('finance:daily')->assertSuccessful();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Late);
});

it('exports invoices and PO records to Excel and renders PDFs', function () {
    Livewire::actingAs($this->finance)->test(Index::class)
        ->call('export')->assertFileDownloaded('Senarai-Invois-Nadi-Qurban.xlsx')
        ->call('exportPo')->assertFileDownloaded('Rekod-PO-Payment-Completed.xlsx');

    Pdf::fake();
    $invoice = Invoice::query()->firstOrFail();
    $this->actingAs($this->finance)->get(route('finance.invoice.pdf', $invoice))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.finance-doc' && $pdf->viewData['doc']->number === $invoice->invoice_no);
});

it('lets finance open completed vendor payment receipts', function () {
    /** @var User $user */
    $user = $this->finance;
    $payment = VendorPayment::query()->where('status', 'completed')->firstOrFail();

    Livewire::actingAs($user)->test(Index::class)
        ->call('viewPo', $payment->id)
        ->assertSet('showPo', true)
        ->assertSee($payment->purchaseOrder->po_no)
        ->call('openReceipt')
        ->assertSet('showReceipt', true)
        ->assertSee('RESIT BAYARAN');

    Pdf::fake();
    $this->actingAs($user)->get(route('vendors.po.pdf', $payment->purchase_order_id))->assertOk();
});
