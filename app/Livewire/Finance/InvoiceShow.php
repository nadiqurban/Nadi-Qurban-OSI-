<?php

namespace App\Livewire\Finance;

use App\Actions\Finance\Invoices;
use App\Enums\InvoiceStatus;
use App\Enums\Module;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Kewangan detail view (Kewangan.dc.html isDetail): Bil Kepada, items, Ringkasan Bayaran, Rekod Transaksi, Sahkan Bayaran. */
#[Layout('layouts::app')]
#[Title('Butiran Invois')]
class InvoiceShow extends Component
{
    public Invoice $invoice;

    public bool $showPay = false;

    public string $payAmount = '';

    public string $payMethod = 'FPX Online';

    public string $payReference = '';

    public string $payDate = '';

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load(['items', 'payments']);
    }

    public function openPay(): void
    {
        $this->authorize(Module::Finance->managePermission());
        $this->resetErrorBag();
        $this->payAmount = number_format($this->invoice->balanceSen() / 100, 2, '.', '');
        $this->payMethod = 'FPX Online';
        $this->payReference = '';
        $this->payDate = today()->toDateString();
        $this->showPay = true;
    }

    public function confirmPayment(Invoices $invoices): void
    {
        $this->authorize(Module::Finance->managePermission());
        $this->validate([
            'payAmount' => ['required', 'regex:/^[0-9,]*\.?[0-9]{0,2}$/'],
            'payMethod' => ['required', Rule::in(InvoicePayment::METHODS)],
            'payReference' => ['nullable', 'string', 'max:60'],
            'payDate' => ['required', 'date', 'before_or_equal:today'],
        ], [], ['payAmount' => 'Jumlah', 'payMethod' => 'Kaedah', 'payDate' => 'Tarikh Bayar']);

        /** @var User $actor */
        $actor = auth()->user();
        $amount = (int) round((float) str_replace(',', '', $this->payAmount) * 100);
        $paidAt = Carbon::parse($this->payDate)->setTimeFrom(now());

        $invoices->recordPayment($this->invoice, $amount, $this->payMethod, $this->payReference, $paidAt, $actor);

        $this->invoice->refresh()->load(['items', 'payments']);
        $this->showPay = false;
        $this->dispatch('toast', message: $this->invoice->status === InvoiceStatus::Paid
            ? "Invois {$this->invoice->invoice_no} dijelaskan sepenuhnya."
            : 'Bayaran '.rm($amount).' direkod.');
    }

    /**
     * Rekod Transaksi timeline (newest first), as in the design.
     *
     * @return list<array{icon: string, tone: string, title: string, time: string}>
     */
    public function timeline(): array
    {
        $inv = $this->invoice;
        $items = [];

        if ($inv->balanceSen() > 0) {
            $items[] = ['icon' => 'hourglass-medium', 'tone' => $inv->status === InvoiceStatus::Late ? 'danger' : 'warning',
                'title' => 'Menunggu baki '.rm($inv->balanceSen()), 'time' => 'Tempoh '.$this->dayMonth($inv->due_date)];
        }

        $running = 0;
        $rows = [];
        foreach ($inv->payments as $p) {
            $running += $p->amount_sen;
            $label = $running >= $inv->total_sen ? ($running === $p->amount_sen ? 'Bayaran penuh' : 'Baki') : 'Deposit';
            $rows[] = ['icon' => 'fill check', 'tone' => 'success', 'title' => $label.' '.rm($p->amount_sen).' diterima', 'time' => $this->stamp($p->paid_at)];
        }

        return [...$items, ...array_reverse($rows), ['icon' => 'receipt', 'tone' => 'primary', 'title' => 'Invois dijana', 'time' => $this->stamp($inv->created_at)]];
    }

    private function dayMonth(Carbon $at): string
    {
        return (string) preg_replace('/ \d{4}$/', '', tarikh($at));
    }

    private function stamp(Carbon $at): string
    {
        return $this->dayMonth($at).', '.$at->format('H:i');
    }

    public function render(): mixed
    {
        $inv = $this->invoice;
        $last = $inv->payments->last();

        return view('livewire.finance.invoice-show', [
            'canManage' => auth()->user()?->can(Module::Finance->managePermission()) ?? false,
            'payInfo' => [
                'Kaedah' => $last->method ?? '-',
                ($inv->status === InvoiceStatus::Paid ? 'Jumlah Dibayar' : 'Deposit Dibayar') => rm($inv->paid_sen),
                'Baki' => rm($inv->balanceSen()),
                'Tarikh Akhir' => tarikh($inv->due_date),
            ],
            'termDays' => (int) $inv->issue_date->diffInDays($inv->due_date),
        ]);
    }
}
