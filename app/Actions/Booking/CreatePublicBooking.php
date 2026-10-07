<?php

namespace App\Actions\Booking;

use App\Actions\Orders\CreateOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Severity;
use App\Models\Agent;
use App\Models\Order;
use App\Models\Product;
use App\Support\Audit;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Tempahan Awam "Bayar": the customer's own booking (no staff actor). Same order
 * creation as the staff screen, plus the agent whose link brought it and the
 * commission snapshot (product commission × quantity).
 *  - FPX (CHIP): "Menunggu Bayaran" until CHIP confirms → verified automatically.
 *  - Pindahan Bank / Cek: "Diterima" with the proof → HQ Pengesahan Bayaran.
 */
class CreatePublicBooking
{
    public function __construct(private readonly CreateOrder $create) {}

    /**
     * @param  array{name: string, phone: string, email: string, address: string, postcode: ?string, city: ?string, state: string}  $customer
     * @param  list<string>  $participants
     */
    public function handle(array $customer, Product $product, int $quantity, array $participants, ?string $promoCode, PaymentMethod $method, ?UploadedFile $proof, ?Agent $agent): Order
    {
        return DB::transaction(function () use ($customer, $product, $quantity, $participants, $promoCode, $method, $proof, $agent) {
            $order = $this->create->handle($customer, [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'year' => (int) app(Settings::class)->get('season.year', now()->year),
                'promo_code' => $promoCode,
                'payment_method' => $method->value,
                'notes' => 'Tempahan awam dalam talian — lafaz akad dipersetujui pelanggan semasa tempahan.',
            ], $participants, $method === PaymentMethod::Fpx ? null : $proof, null);

            $online = $method === PaymentMethod::Fpx;

            $order->forceFill([
                'source' => 'public',
                'agent_id' => $agent?->id,
                'commission_sen' => $agent ? $product->commission_sen * $quantity : 0,
                'status' => $online ? OrderStatus::AwaitingPayment : OrderStatus::Accepted,
                'accepted_at' => $online ? null : now(),
            ])->save();

            $order->payment?->forceFill(['channel' => $online ? 'CHIP' : $method->label()])->save();

            Audit::log('booking.created', "Tempahan awam {$order->order_no} ({$method->label()})".($agent ? " melalui ejen {$agent->code}" : ''),
                $order, Severity::Info, ['total_sen' => $order->total_sen, 'agent' => $agent?->code], null, 'orders');

            return $order;
        });
    }
}
