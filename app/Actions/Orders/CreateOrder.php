<?php

namespace App\Actions\Orders;

use App\Actions\Pricing\CalculatePrice;
use App\Actions\Pricing\PriceBreakdown;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\Sequence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Simpan Tempahan": finds/creates the customer (by phone), prices the order from
 * the product (single source of price) + promo, numbers it NQ-{SVC}-{ANI}-{seq6},
 * records the payment (+ proof) and the first stage "Tempahan Diterima".
 */
class CreateOrder
{
    public function __construct(
        private readonly CalculatePrice $pricing,
        private readonly AdvanceStage $stage,
    ) {}

    /**
     * @param  array{name: string, phone: string, email?: ?string, address?: ?string, postcode?: ?string, city?: ?string, state?: ?string}  $customer
     * @param  array{product_id: int, quantity: int, year: int, implementation_date?: ?string, promo_code?: ?string, payment_method: string, notes?: ?string, is_instalment?: bool, order_no?: string, price?: PriceBreakdown, country_id?: int}  $order
     * @param  list<string>  $participants
     *
     * order_no / price / country_id: a reserved number and price snapshot (instalment plans "Hantar").
     */
    public function handle(array $customer, array $order, array $participants, ?UploadedFile $proof, ?User $actor): Order
    {
        return DB::transaction(function () use ($customer, $order, $participants, $proof, $actor) {
            $product = Product::query()->with(['package', 'country'])->lockForUpdate()->findOrFail($order['product_id']);

            if (! $product->is_active) {
                throw ValidationException::withMessages(['productId' => 'Produk ini tidak aktif.']);
            }

            if ($order['quantity'] > $product->stock) {
                throw ValidationException::withMessages(['quantity' => "Stok tidak mencukupi — baki {$product->stock} unit."]);
            }

            $promo = null;

            if (! empty($order['promo_code']) && ! isset($order['price'])) {
                $promo = PromoCode::findUsable($order['promo_code']);

                if (! $promo) {
                    throw ValidationException::withMessages(['promo' => 'Kod promosi tidak sah, telah tamat atau mencapai had.']);
                }
            }

            $price = $order['price'] ?? $this->pricing->handle($product, $order['quantity'], $promo);
            $client = $this->resolveCustomer($customer);
            $seq = isset($order['order_no']) ? (int) substr($order['order_no'], -6) : Sequence::next('order', 1249);
            $method = PaymentMethod::from($order['payment_method']);

            $model = Order::query()->create([
                'order_no' => $order['order_no'] ?? sprintf('NQ-%s-%s-%06d', $product->service->code(), $product->animal->code(), $seq),
                'tracking_no' => sprintf('NQT-%d-%06d', $order['year'], $seq),
                'tracking_token' => Str::random(40),
                'customer_id' => $client->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'service' => $product->service,
                'animal' => $product->animal,
                'package_name' => $product->package->name,
                'country_id' => $order['country_id'] ?? $product->country_id,
                'quantity' => $order['quantity'],
                'year' => $order['year'],
                'implementation_date' => $order['implementation_date'] ?? null,
                'unit_price_sen' => $price->unitPriceSen,
                'subtotal_sen' => $price->subtotalSen,
                'discount_sen' => $price->discountSen,
                'total_sen' => $price->totalSen,
                'promo_code_id' => $promo?->id,
                'promo_code' => $promo->code ?? $price->promoCode,
                'payment_method' => $method,
                'is_instalment' => (bool) ($order['is_instalment'] ?? false),
                'status' => OrderStatus::Draft,
                'stage' => OrderStage::Received,
                'notes' => $order['notes'] ?? null,
                'created_by' => $actor?->id,
            ]);

            $this->storeParticipants($model, $participants, $client->name);

            $payment = Payment::query()->create([
                'order_id' => $model->id,
                'method' => $method,
                'amount_sen' => $price->totalSen,
                'paid_at' => $proof ? now() : null,
                'status' => PaymentStatus::Pending,
            ]);

            if ($proof) {
                $payment->addMedia($proof)->usingFileName('bukti-'.$model->order_no.'.'.$proof->extension())->toMediaCollection('proof');
            }

            $this->stage->handle($model, OrderStage::Received, $actor, 'Tempahan dicipta');

            return $model;
        });
    }

    /** @param  array{name: string, phone: string, email?: ?string, address?: ?string, postcode?: ?string, city?: ?string, state?: ?string}  $data */
    private function resolveCustomer(array $data): Customer
    {
        return Customer::resolve($data);
    }

    /** @param  list<string>  $names */
    private function storeParticipants(Order $order, array $names, string $customerName): void
    {
        foreach (range(1, $order->quantity) as $position) {
            $name = trim($names[$position - 1] ?? '');

            $order->participants()->create([
                'position' => $position,
                'name' => $name !== '' ? $name : ($position === 1 ? $customerName : null),
            ]);
        }
    }
}
