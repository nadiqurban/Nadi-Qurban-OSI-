<?php

namespace App\Actions\Pipeline;

use App\Enums\OrderStage;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\OrderStageHistory;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agihan "Batal": the only backwards move in the pipeline — explicit, audited,
 * and allowed only before the vendor uploads a report.
 */
class CancelAllocation
{
    public function handle(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor) {
            /** @var Order $order */
            $order = Order::query()->with('allocation.vendor')->lockForUpdate()->findOrFail($order->id);

            if (! $order->allocation || $order->stage !== OrderStage::Executing) {
                throw ValidationException::withMessages(['order' => "Agihan {$order->order_no} tidak boleh dibatalkan (laporan sudah dimuat naik)."]);
            }

            $vendor = $order->allocation->vendor->name;
            $order->allocation->delete();

            $order->stageHistories()
                ->whereIn('stage', [OrderStage::CountryAssigned->value, OrderStage::VendorAssigned->value, OrderStage::Executing->value])
                ->delete();

            $order->forceFill(['stage' => OrderStage::AkadDone])->save();

            OrderStageHistory::query()->create([
                'order_id' => $order->id,
                'stage' => OrderStage::AkadDone,
                'user_id' => $actor->id,
                'note' => "Agihan kepada {$vendor} dibatalkan",
                'created_at' => now(),
            ]);

            Audit::log('allocation.cancelled', "Agihan {$order->order_no} dibatalkan", $order, Severity::Warning, ['vendor' => $vendor], $actor, 'orders');
        });
    }
}
