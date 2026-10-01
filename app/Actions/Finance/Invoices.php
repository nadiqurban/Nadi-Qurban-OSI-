<?php

namespace App\Actions\Finance;

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use App\Support\Sequence;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Kewangan invoices: create (INV-{year}-{seq4}), record/confirm a payment and
 * keep the status (Deposit / Dibayar / Tertunggak / Lewat) derived from data.
 */
class Invoices
{
    /**
     * @param  array{company: array<string, string>, name: string, phone?: string|null, email?: string|null, address?: string|null, order?: string|null, due: string, note?: string|null, items: list<array{description: string, quantity: int, unit_price_sen: int}>}  $data
     */
    public function create(array $data, User $actor): Invoice
    {
        $items = $this->cleanItems($data['items']);
        $total = array_sum(array_map(fn ($i) => $i['quantity'] * $i['unit_price_sen'], $items));
        $due = Carbon::parse($data['due'])->startOfDay();
        $orderNo = trim((string) ($data['order'] ?? '')) ?: null;

        return DB::transaction(function () use ($data, $items, $total, $due, $orderNo, $actor) {
            $year = (int) app(Settings::class)->get('season.year', now()->year);
            $invoice = Invoice::query()->create([
                'invoice_no' => sprintf('INV-%d-%04d', $year, Sequence::next("invoice-{$year}", 149)),
                'order_id' => $orderNo ? Order::query()->where('order_no', $orderNo)->value('id') : null,
                'order_no' => $orderNo,
                'customer_name' => trim($data['name']),
                'customer_phone' => $data['phone'] ?? null,
                'customer_email' => $data['email'] ?? null,
                'customer_address' => $data['address'] ?? null,
                'company' => $data['company'],
                'subtotal_sen' => $total,
                'tax_sen' => 0,
                'total_sen' => $total,
                'paid_sen' => 0,
                'issue_date' => today(),
                'due_date' => $due,
                'status' => InvoiceStatus::derive($total, 0, $due),
                'notes' => $data['note'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($items as $pos => $item) {
                $invoice->items()->create($item + ['line_total_sen' => $item['quantity'] * $item['unit_price_sen'], 'position' => $pos]);
            }

            Audit::log('invoice.created', "Invois {$invoice->invoice_no} dicipta untuk {$invoice->customer_name} (".rm($total).')', $invoice,
                properties: ['ref' => $invoice->invoice_no], causer: $actor, logName: 'finance');

            return $invoice;
        });
    }

    /** Record a payment (Sahkan Bayaran) and re-derive the status. */
    public function recordPayment(Invoice $invoice, int $amountSen, string $method, ?string $reference, Carbon $paidAt, User $actor): InvoicePayment
    {
        if ($amountSen <= 0 || $amountSen > $invoice->balanceSen()) {
            throw ValidationException::withMessages(['payAmount' => 'Jumlah mesti antara RM 0.01 dan baki '.rm($invoice->balanceSen()).'.']);
        }

        $payment = DB::transaction(function () use ($invoice, $amountSen, $method, $reference, $paidAt, $actor) {
            $locked = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $payment = $locked->payments()->create([
                'amount_sen' => $amountSen,
                'method' => $method,
                'reference' => $reference ?: null,
                'paid_at' => $paidAt,
                'recorded_by' => $actor->id,
            ]);

            $locked->paid_sen += $amountSen;
            $locked->status = InvoiceStatus::derive($locked->total_sen, $locked->paid_sen, $locked->due_date);
            $locked->save();

            Audit::log('invoice.payment', 'Bayaran '.rm($amountSen)." direkod untuk {$locked->invoice_no} ({$method})", $locked,
                properties: ['ref' => $locked->invoice_no, 'amount_sen' => $amountSen], causer: $actor, logName: 'finance');

            $invoice->setRawAttributes($locked->getAttributes(), true);

            return $payment;
        });

        if ($invoice->status === InvoiceStatus::Paid) {
            event(new InvoicePaid($invoice));
        }

        return $payment;
    }

    /** Scheduler: flag overdue invoices as Lewat (and un-flag if the due date moved). Returns rows changed. */
    public function refreshStatuses(): int
    {
        $changed = 0;

        Invoice::query()->where('status', '!=', InvoiceStatus::Paid)->chunkById(200, function ($invoices) use (&$changed) {
            foreach ($invoices as $invoice) {
                $status = InvoiceStatus::derive($invoice->total_sen, $invoice->paid_sen, $invoice->due_date);

                if ($status !== $invoice->status) {
                    $invoice->update(['status' => $status]);
                    $changed++;
                }
            }
        });

        return $changed;
    }

    /**
     * @param  list<array{description: string, quantity: int, unit_price_sen: int}>  $items
     * @return list<array{description: string, quantity: int, unit_price_sen: int}>
     */
    private function cleanItems(array $items): array
    {
        $clean = array_values(array_filter($items, fn ($i) => trim($i['description']) !== '' && $i['quantity'] > 0));

        if ($clean === []) {
            throw ValidationException::withMessages(['draft.items' => 'Tambah sekurang-kurangnya satu item.']);
        }

        return array_map(fn ($i) => ['description' => trim($i['description']), 'quantity' => $i['quantity'], 'unit_price_sen' => $i['unit_price_sen']], $clean);
    }
}
