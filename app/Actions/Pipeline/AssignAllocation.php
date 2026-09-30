<?php

namespace App\Actions\Pipeline;

use App\Actions\Orders\AdvanceStage;
use App\Enums\OrderStage;
use App\Enums\Severity;
use App\Enums\VendorStatus;
use App\Models\Allocation;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agihan Negara "Hantar": sets the implementation country + an ACTIVE vendor of
 * that country, then country_assigned → vendor_assigned → executing.
 */
class AssignAllocation
{
    public function __construct(private readonly AdvanceStage $stage) {}

    public function handle(Order $order, int $countryId, int $vendorId, User $actor): Allocation
    {
        return DB::transaction(function () use ($order, $countryId, $vendorId, $actor) {
            /** @var Order $order */
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->stage !== OrderStage::AkadDone) {
                throw ValidationException::withMessages(['order' => "Tempahan {$order->order_no} belum sedia untuk diagih."]);
            }

            $vendor = Vendor::query()->find($vendorId);

            if (! $vendor || $vendor->status !== VendorStatus::Active || $vendor->country_id !== $countryId) {
                throw ValidationException::withMessages(['vendor' => 'Pilih vendor aktif yang berdaftar untuk negara ini.']);
            }

            $countryChanged = $order->country_id !== $countryId;
            $order->forceFill(['country_id' => $countryId])->save();

            $allocation = Allocation::query()->create([
                'order_id' => $order->id,
                'country_id' => $countryId,
                'vendor_id' => $vendor->id,
                'allocated_by' => $actor->id,
                'sent_at' => now(),
            ]);

            if ($countryChanged) {
                Audit::log('order.country', "Negara {$order->order_no} ditukar", $order, Severity::Info, ['country_id' => $countryId], $actor, 'orders');
            }

            $this->stage->handle($order, OrderStage::CountryAssigned, $actor);
            $this->stage->handle($order, OrderStage::VendorAssigned, $actor, $vendor->name);
            $this->stage->handle($order, OrderStage::Executing, $actor);

            return $allocation;
        });
    }
}
