<?php

namespace App\Actions\Booking;

use App\Actions\Orders\VerifyPayment;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * CHIP confirmed the online payment of a public booking: mark it paid and verify it
 * automatically (no HQ step), so it moves straight on to Lafaz Akad. Idempotent.
 */
class ConfirmBookingPayment
{
    public function __construct(private readonly VerifyPayment $verify) {}

    public function handle(Order $order, string $reference, string $channel): void
    {
        DB::transaction(function () use ($order, $reference, $channel) {
            /** @var Order $order */
            $order = Order::query()->with('payment')->lockForUpdate()->findOrFail($order->id);

            if ($order->status !== OrderStatus::AwaitingPayment) {
                return;
            }

            $order->payment?->forceFill([
                'paid_at' => now(),
                'channel' => $channel,
                'gateway_status' => 'berjaya',
                'reference' => $reference,
            ])->save();

            $order->forceFill(['status' => OrderStatus::Accepted, 'accepted_at' => now()])->save();

            $this->verify->handle($order, null);
        });
    }
}
