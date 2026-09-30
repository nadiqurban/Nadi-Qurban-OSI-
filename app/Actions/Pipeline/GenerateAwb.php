<?php

namespace App\Actions\Pipeline;

use App\Actions\Orders\AdvanceStage;
use App\Enums\Courier;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PostType;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Jana & Simpan" / "Postage Pukal": creates the shipment (consignment {prefix}{6}MY),
 * issues the certificates and closes the order: awb_generated → certificate_posted
 * → completed, status Selesai.
 */
class GenerateAwb
{
    public function __construct(
        private readonly AdvanceStage $stage,
        private readonly IssueCertificates $certificates,
    ) {}

    /**
     * @param  array{address?: ?string, postcode?: ?string, city?: ?string, state?: ?string}  $address  overrides; defaults to the customer's
     */
    public function handle(Order $order, Courier $courier, PostType $postType, array $address, User $actor): Shipment
    {
        return DB::transaction(function () use ($order, $courier, $postType, $address, $actor) {
            /** @var Order $order */
            $order = Order::query()->with('customer')->lockForUpdate()->findOrFail($order->id);

            if ($order->stage !== OrderStage::FinalReport) {
                throw ValidationException::withMessages(['order' => "Tempahan {$order->order_no} belum sedia untuk AWB."]);
            }

            $c = $order->customer;
            $street = trim((string) ($address['address'] ?? '')) ?: $c->address;

            if (! $street) {
                throw ValidationException::withMessages(['address' => "Alamat penghantaran {$order->order_no} diperlukan."]);
            }

            $shipment = Shipment::query()->create([
                'order_id' => $order->id,
                'courier' => $courier,
                'post_type' => $postType,
                'consignment_no' => $this->consignment($courier, $order),
                'recipient_name' => $c->name,
                'phone' => $c->phone,
                'address' => $street,
                'postcode' => ($address['postcode'] ?? null) ?: $c->postcode,
                'city' => ($address['city'] ?? null) ?: $c->city,
                'state' => ($address['state'] ?? null) ?: $c->state,
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ]);

            $this->certificates->handle(collect([$order]), $actor);

            $this->stage->handle($order, OrderStage::AwbGenerated, $actor, $courier->label().' · '.$shipment->consignment_no);
            $this->stage->handle($order, OrderStage::CertificatePosted, $actor);
            $this->stage->handle($order, OrderStage::Completed, $actor);

            $order->forceFill(['status' => OrderStatus::Completed])->save();

            return $shipment;
        });
    }

    /** Placeholder consignment until the EasyParcel integration (PRD §6.7, Phase 2). */
    private function consignment(Courier $courier, Order $order): string
    {
        $no = $courier->consignmentFor($order->order_no);
        $suffix = 1;

        while (Shipment::query()->where('consignment_no', $no)->exists()) {
            $no = $courier->prefix().str_pad((string) (random_int(100000, 999999) + $suffix++), 6, '0', STR_PAD_LEFT).'MY';
        }

        return $no;
    }
}
