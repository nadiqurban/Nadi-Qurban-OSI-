<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Severity;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** "Batal" in Pengesahan Bayaran: payment rejected with a reason, order back to Menunggu Bayaran. */
class RejectPayment
{
    public function handle(Order $order, string $reason, User $actor): void
    {
        DB::transaction(function () use ($order, $reason, $actor) {
            /** @var Order $order */
            $order = Order::query()->with('payment')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::Accepted || $order->payment?->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages(['order' => "Tempahan {$order->order_no} tidak menunggu pengesahan bayaran."]);
            }

            $order->payment->forceFill(['status' => PaymentStatus::Rejected, 'rejection_reason' => $reason])->save();
            $order->forceFill(['status' => OrderStatus::AwaitingPayment, 'accepted_at' => null])->save();

            Audit::log('payment.rejected', "Bayaran {$order->order_no} ditolak", $order, Severity::Warning, ['reason' => $reason], $actor, 'payments');
        });
    }
}
