<?php

namespace App\Actions\Orders;

use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Sahkan" in Pengesahan Bayaran: payment verified → stage payment_verified (the
 * order moves on to Lafaz Akad), status Dalam Proses, promo usage counted, stock
 * deducted. All in one transaction.
 */
class VerifyPayment
{
    public function __construct(private readonly AdvanceStage $stage) {}

    public function handle(Order $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor) {
            /** @var Order $order */
            $order = Order::query()->with('payment')->lockForUpdate()->findOrFail($order->id);
            $payment = $order->payment;

            if ($order->status !== OrderStatus::Accepted || ! $payment || $payment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages(['order' => "Tempahan {$order->order_no} tidak menunggu pengesahan bayaran."]);
            }

            if ($order->product_id) {
                $product = Product::withTrashed()->lockForUpdate()->findOrFail($order->product_id);

                if ($product->stock < $order->quantity) {
                    throw ValidationException::withMessages(['order' => "Stok {$product->name} tidak mencukupi (baki {$product->stock})."]);
                }

                $product->decrement('stock', $order->quantity);
            }

            if ($order->promo_code_id) {
                PromoCode::withTrashed()->whereKey($order->promo_code_id)
                    ->incrementEach(['used_count' => 1, 'total_discount_sen' => $order->discount_sen]);
            }

            $payment->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'paid_at' => $payment->paid_at ?? now(),
            ])->save();

            $order->forceFill(['status' => OrderStatus::InProgress])->save();

            Audit::log('payment.verified', "Bayaran {$order->order_no} disahkan", $order, Severity::Warning, [
                'amount_sen' => $payment->amount_sen,
            ], $actor, 'payments');

            $this->stage->handle($order, OrderStage::PaymentVerified, $actor);
        });
    }
}
