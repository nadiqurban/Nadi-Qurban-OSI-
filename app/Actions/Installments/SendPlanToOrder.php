<?php

namespace App\Actions\Installments;

use App\Actions\Orders\CreateOrder;
use App\Actions\Pricing\PriceBreakdown;
use App\Enums\InstallmentPlanStatus;
use App\Enums\OrderStatus;
use App\Enums\Severity;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Hantar": a fully paid plan becomes an Order (reserved number, price snapshot,
 * ANSURAN badge) in Pengesahan Bayaran (status Diterima, payment pending HQ check).
 */
class SendPlanToOrder
{
    public function __construct(private readonly CreateOrder $createOrder) {}

    public function handle(InstallmentPlan $plan, User $actor): Order
    {
        return DB::transaction(function () use ($plan, $actor) {
            /** @var InstallmentPlan $plan */
            $plan = InstallmentPlan::query()->with(['customer', 'installments'])->lockForUpdate()->findOrFail($plan->id);

            if ($plan->sent_at || $plan->order_id) {
                throw ValidationException::withMessages(['plan' => "Pelan {$plan->order_no} telah dihantar."]);
            }

            if ($plan->status === InstallmentPlanStatus::Cancelled || ! $plan->isFullyPaid()) {
                throw ValidationException::withMessages(['plan' => "Pelan {$plan->order_no} belum selesai dibayar."]);
            }

            $c = $plan->customer;
            $order = $this->createOrder->handle(
                ['name' => $c->name, 'phone' => $c->phone, 'email' => $c->email, 'address' => $c->address, 'postcode' => $c->postcode, 'city' => $c->city, 'state' => $c->state],
                [
                    'product_id' => (int) $plan->product_id,
                    'quantity' => $plan->quantity,
                    'year' => $plan->year,
                    'implementation_date' => $plan->implementation_date?->toDateString(),
                    'payment_method' => $plan->payment_method->orderMethod()->value,
                    'is_instalment' => true,
                    'order_no' => $plan->order_no,
                    'country_id' => $plan->country_id,
                    'notes' => "Ansuran {$plan->months} bulan · resit {$plan->receiptNo()}",
                    'price' => new PriceBreakdown($plan->unit_price_sen, $plan->quantity, $plan->subtotal_sen, $plan->discount_sen, $plan->total_sen, $plan->deposit_sen, $plan->promo_code),
                ],
                $plan->participantList(),
                null,
                $actor,
            );

            $order->forceFill(['status' => OrderStatus::Accepted, 'accepted_at' => now()])->save();
            $order->payment?->forceFill(['paid_at' => $plan->installments->max('paid_at') ?? now(), 'channel' => "Ansuran {$plan->months} bulan"])->save();

            $plan->forceFill(['sent_at' => now(), 'order_id' => $order->id])->save();

            Audit::log('installment.sent', "Pelan ansuran {$plan->order_no} dihantar ke Pengesahan Bayaran", $plan, Severity::Info, [], $actor, 'installments');

            return $order;
        });
    }
}
