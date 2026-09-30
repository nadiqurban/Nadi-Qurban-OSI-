<?php

namespace App\Actions\Vendors;

use App\Enums\PoStatus;
use App\Enums\Service;
use App\Enums\Severity;
use App\Enums\VendorPaymentStatus;
use App\Enums\VendorStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Audit;
use App\Support\Sequence;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase Order lifecycle: create (Draft) → send → vendor accepts → in progress
 * (report submitted) → completed (report verified). Cancel any time before Completed.
 * Every PO gets a Pending vendor payment record for Bayaran.
 */
class PurchaseOrders
{
    public function __construct(private readonly Settings $settings) {}

    public function usdRate(): float
    {
        return (float) $this->settings->get('finance.usd_rate', '4.70');
    }

    /**
     * @param  array{service: string, animal_label: string, quantity: int, unit_price: float, currency: string, implementation_date?: ?string, notes?: ?string}  $data
     */
    public function create(Vendor $vendor, array $data, User $actor): PurchaseOrder
    {
        if ($vendor->status === VendorStatus::Suspended) {
            throw ValidationException::withMessages(['vendor' => 'Vendor ini digantung — PO tidak boleh dicipta.']);
        }

        return DB::transaction(function () use ($vendor, $data, $actor) {
            $year = (int) $this->settings->get('season.year', now()->year);
            $rate = $data['currency'] === 'USD' ? $this->usdRate() : 1.0;
            $unit = (int) round($data['unit_price'] * 100);
            $total = $unit * $data['quantity'];
            $seq = Sequence::next('po-'.$year, 1);
            $company = $this->settings->group('company');

            $po = PurchaseOrder::query()->create([
                'po_no' => sprintf('NQ-PO-%d-%04d', $year, $seq),
                'vendor_id' => $vendor->id,
                'service' => Service::from($data['service']),
                'animal_label' => $data['animal_label'],
                'quantity' => $data['quantity'],
                'currency' => $data['currency'],
                'exchange_rate' => $rate,
                'unit_price_minor' => $unit,
                'total_minor' => $total,
                'total_rm_sen' => (int) round($total * $rate),
                'implementation_date' => $data['implementation_date'] ?? null,
                'status' => PoStatus::Draft,
                'billing_address' => trim(($company['name'] ?? '')."\n".($company['address'] ?? '')."\nNo. SST: ".($company['sst'] ?? '')),
                'shipping_address' => $vendor->companyName()."\nTapak Pelaksanaan, ".$vendor->country->name."\nPIC: ".($vendor->pic_name ?: '-')."\nTel: ".($vendor->phone ?: '-'),
                'notes' => $data['notes'] ?? null,
                'payment_terms' => '50% deposit, 50% selesai',
                'reference' => sprintf('REF/NQ/%s/%d/%04d', mb_strtoupper(mb_substr($vendor->country->name, 0, 3)), $year, $seq),
                'created_by' => $actor->id,
            ]);

            $po->payment()->create(['vendor_id' => $vendor->id, 'amount_sen' => $po->total_rm_sen, 'status' => VendorPaymentStatus::Pending]);

            $this->log($po, 'po.created', "Purchase Order {$po->po_no} dicipta ({$po->quantity} × {$po->animal_label})", $actor);

            return $po;
        });
    }

    /**
     * "Simpan Maklumat": rate table, billing/shipping addresses and notes.
     *
     * @param  array{service_label?: string, quantity: int, unit_price: float, billing_address: ?string, shipping_address: ?string, notes: ?string}  $data
     */
    public function update(PurchaseOrder $po, array $data, User $actor): void
    {
        if (in_array($po->status, [PoStatus::Completed, PoStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['po' => 'PO yang selesai atau dibatalkan tidak boleh diubah.']);
        }

        DB::transaction(function () use ($po, $data, $actor) {
            $unit = (int) round($data['unit_price'] * 100);
            $total = $unit * $data['quantity'];

            $po->fill([
                'animal_label' => $data['service_label'] ?? $po->animal_label,
                'quantity' => $data['quantity'],
                'unit_price_minor' => $unit,
                'total_minor' => $total,
                'total_rm_sen' => (int) round($total * (float) $po->exchange_rate),
                'billing_address' => $data['billing_address'],
                'shipping_address' => $data['shipping_address'],
                'notes' => $data['notes'],
            ])->save();

            if ($po->payment && $po->payment->status !== VendorPaymentStatus::Completed) {
                $po->payment->forceFill(['amount_sen' => $po->total_rm_sen])->save();
            }

            $this->log($po, 'po.updated', "Maklumat PO {$po->po_no} dikemaskini", $actor);
        });
    }

    public function send(PurchaseOrder $po, User $actor): void
    {
        $this->move($po, [PoStatus::Draft], PoStatus::Sent, ['sent_at' => now()], 'po.sent', "PO {$po->po_no} dihantar kepada vendor", $actor);
    }

    /** "Accept PO" — the vendor PIC of this vendor (or HQ on their behalf). */
    public function accept(PurchaseOrder $po, User $actor): void
    {
        if ($actor->isVendorPic() && $actor->vendor_id !== $po->vendor_id) {
            abort(403);
        }

        $this->move($po, [PoStatus::Sent], PoStatus::Accepted, ['accepted_at' => now(), 'accepted_by' => $actor->id], 'po.accepted', "PO {$po->po_no} diterima vendor", $actor);
    }

    public function startProgress(PurchaseOrder $po, User $actor): void
    {
        if ($po->status === PoStatus::Accepted) {
            $this->move($po, [PoStatus::Accepted], PoStatus::InProgress, ['in_progress_at' => now()], 'po.in_progress', "PO {$po->po_no} dalam pelaksanaan", $actor);
        }
    }

    public function complete(PurchaseOrder $po, User $actor): void
    {
        $this->move($po, [PoStatus::Accepted, PoStatus::InProgress], PoStatus::Completed, ['completed_at' => now()], 'po.completed', "PO {$po->po_no} ditandakan Completed", $actor);
    }

    public function cancel(PurchaseOrder $po, User $actor): void
    {
        if ($po->payment?->status === VendorPaymentStatus::Completed) {
            throw ValidationException::withMessages(['po' => 'PO yang telah dibayar tidak boleh dibatalkan.']);
        }

        $this->move($po, [PoStatus::Draft, PoStatus::Sent, PoStatus::Accepted, PoStatus::InProgress], PoStatus::Cancelled, ['cancelled_at' => now()], 'po.cancelled', "PO {$po->po_no} dibatalkan", $actor, Severity::Warning);
    }

    /**
     * @param  list<PoStatus>  $from
     * @param  array<string, mixed>  $attributes
     */
    private function move(PurchaseOrder $po, array $from, PoStatus $to, array $attributes, string $event, string $description, User $actor, Severity $severity = Severity::Info): void
    {
        DB::transaction(function () use ($po, $from, $to, $attributes, $event, $description, $actor, $severity) {
            /** @var PurchaseOrder $locked */
            $locked = PurchaseOrder::query()->lockForUpdate()->findOrFail($po->id);

            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['po' => "PO {$locked->po_no} berstatus {$locked->status->label()} — tindakan tidak sah."]);
            }

            $locked->forceFill(['status' => $to] + $attributes)->save();
            $po->setRawAttributes($locked->getAttributes(), true);
            $this->log($locked, $event, $description, $actor, $severity);
        });
    }

    private function log(PurchaseOrder $po, string $event, string $description, User $actor, Severity $severity = Severity::Info): void
    {
        Audit::log($event, $description, $po, $severity, ['vendor_id' => $po->vendor_id, 'ref' => $po->po_no], $actor, 'vendors');
    }

    public static function parseDate(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
