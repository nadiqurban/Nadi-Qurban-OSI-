<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Bulk actions from the list: "Diterima" (sends orders to Pengesahan Bayaran) and
 * "Kemaskini Status". Orders already past payment verification keep their status.
 */
class UpdateOrderStatus
{
    /**
     * @param  list<int>  $orderIds
     * @return int number of orders changed
     */
    public function handle(array $orderIds, OrderStatus $status, User $actor): int
    {
        return DB::transaction(function () use ($orderIds, $status, $actor) {
            $changed = 0;

            $orders = Order::query()->with('payment')->whereIn('id', $orderIds)->lockForUpdate()->get();

            foreach ($orders as $order) {
                $verified = $order->payment?->status === PaymentStatus::Verified;

                // Verified or completed orders are driven by the pipeline, not set by hand.
                if ($order->status === $status || $order->status === OrderStatus::Completed || ($verified && $status !== OrderStatus::Cancelled)) {
                    continue;
                }

                $from = $order->status;
                $order->forceFill([
                    'status' => $status,
                    'accepted_at' => $status === OrderStatus::Accepted ? now() : $order->accepted_at,
                ])->save();

                if ($status === OrderStatus::Accepted && $order->payment?->status === PaymentStatus::Rejected) {
                    $order->payment->forceFill(['status' => PaymentStatus::Pending, 'rejection_reason' => null])->save();
                }

                Audit::log('order.status', "{$order->order_no}: {$from->label()} → {$status->label()}", $order,
                    $status === OrderStatus::Cancelled ? Severity::Warning : Severity::Info,
                    ['from' => $from->value, 'to' => $status->value], $actor, 'orders');

                $changed++;
            }

            return $changed;
        });
    }
}
