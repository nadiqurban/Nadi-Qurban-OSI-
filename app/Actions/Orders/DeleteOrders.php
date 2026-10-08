<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Audit;
use App\Support\DashboardStats;
use Illuminate\Support\Facades\DB;

/**
 * "Batal" in Tempahan & Pelanggan: the order is removed for good (no "Dibatalkan" record
 * kept). Participants, payment, akad, allocation, report, AWB and certificates go with it
 * (FK cascade); proof / evidence files are deleted; stock and promo usage taken at
 * payment verification are given back. Completed orders are never deleted.
 */
class DeleteOrders
{
    /**
     * @param  list<int>  $orderIds
     * @return int number of orders deleted
     */
    public function handle(array $orderIds, User $actor): int
    {
        $deleted = DB::transaction(function () use ($orderIds, $actor) {
            $count = 0;

            $orders = Order::query()->with(['payment.media', 'executionReport.media'])
                ->whereIn('id', $orderIds)->lockForUpdate()->get();

            foreach ($orders as $order) {
                if ($order->status === OrderStatus::Completed) {
                    continue;
                }

                if ($order->payment?->status === PaymentStatus::Verified && $order->status !== OrderStatus::Cancelled) {
                    if ($order->product_id) {
                        Product::withTrashed()->whereKey($order->product_id)->increment('stock', $order->quantity);
                    }

                    if ($order->promo_code_id) {
                        PromoCode::withTrashed()->whereKey($order->promo_code_id)->where('used_count', '>', 0)
                            ->decrementEach(['used_count' => 1, 'total_discount_sen' => $order->discount_sen]);
                    }
                }

                // Files first (spatie removes them with the media rows).
                $order->payment?->media->each->delete();
                $order->executionReport?->media->each->delete();

                Audit::log('order.deleted', "Tempahan {$order->order_no} dibatalkan & dipadam", null, Severity::Warning, [
                    'order_no' => $order->order_no,
                    'customer' => $order->customer_id,
                    'total_sen' => $order->total_sen,
                ], $actor, 'orders');

                $order->forceDelete();
                $count++;
            }

            return $count;
        });

        DashboardStats::flush();

        return $deleted;
    }
}
