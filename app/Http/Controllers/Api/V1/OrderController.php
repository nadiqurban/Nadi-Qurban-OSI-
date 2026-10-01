<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\CreateOrder;
use App\Enums\MalaysianState;
use App\Enums\OrderStage;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** /api/v1/orders — look up by order no. or tracking no., status, and create. */
class OrderController
{
    private function find(string $reference): Order
    {
        $ref = strtoupper(trim($reference));

        return Order::query()->with(['customer', 'country', 'participants', 'payment'])
            ->where(fn ($q) => $q->where('order_no', $ref)->orWhere('tracking_no', $ref))
            ->firstOrFail();
    }

    public function show(string $reference): JsonResponse
    {
        return response()->json(['data' => self::present($this->find($reference))]);
    }

    public function status(string $reference): JsonResponse
    {
        $order = $this->find($reference);

        return response()->json(['data' => [
            'order_no' => $order->order_no,
            'tracking_no' => $order->tracking_no,
            'status' => ['code' => $order->status->value, 'label' => $order->status->label()],
            'stage' => ['code' => $order->stage->value, 'label' => $order->stage->label(), 'step' => $order->stage->position() + 1, 'of' => count(OrderStage::cases())],
            'tracking_url' => $order->trackingUrl(),
            'updated_at' => $order->updated_at->toIso8601String(),
        ]]);
    }

    public function store(Request $request, CreateOrder $create): JsonResponse
    {
        $data = $request->validate([
            'customer.name' => ['required', 'string', 'max:150'],
            'customer.phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'customer.email' => ['nullable', 'email', 'max:150'],
            'customer.address' => ['nullable', 'string', 'max:300'],
            'customer.postcode' => ['nullable', 'digits:5'],
            'customer.city' => ['nullable', 'string', 'max:80'],
            'customer.state' => ['required', Rule::enum(MalaysianState::class)],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:700'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'implementation_date' => ['nullable', 'date'],
            'promo_code' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'participants' => ['nullable', 'array', 'max:700'],
            'participants.*' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $create->handle(
            $data['customer'],
            [
                'product_id' => (int) $data['product_id'],
                'quantity' => (int) $data['quantity'],
                'year' => (int) ($data['year'] ?? app(Settings::class)->get('season.year', now()->year)),
                'implementation_date' => $data['implementation_date'] ?? null,
                'promo_code' => $data['promo_code'] ?? null,
                'payment_method' => $data['payment_method'],
                'notes' => trim(($data['notes'] ?? '').' [API: '.$request->user()?->getAttribute('name').']'),
            ],
            array_values(array_map('strval', array_filter($data['participants'] ?? [], fn ($p) => $p !== null && $p !== ''))),
            null,
            null,
        );

        return response()->json(['data' => self::present($order->load(['customer', 'country', 'participants', 'payment']))], 201);
    }

    /** @return array<string, mixed> */
    public static function present(Order $order): array
    {
        return [
            'order_no' => $order->order_no,
            'tracking_no' => $order->tracking_no,
            'service' => $order->service->value,
            'animal' => $order->animal->value,
            'package' => $order->package_name,
            'product' => $order->product_name,
            'country' => $order->country->name,
            'quantity' => $order->quantity,
            'year' => $order->year,
            'implementation_date' => $order->implementation_date?->toDateString(),
            'amount' => ['total_sen' => $order->total_sen, 'discount_sen' => $order->discount_sen, 'formatted' => rm($order->total_sen)],
            'payment' => ['method' => $order->payment_method->value, 'status' => $order->payment?->status->value],
            'status' => $order->status->value,
            'stage' => $order->stage->value,
            'customer' => ['name' => $order->customer->name, 'phone' => $order->customer->phone, 'email' => $order->customer->email],
            'participants' => $order->participants->pluck('name')->filter()->values(),
            'tracking_url' => $order->trackingUrl(),
            'created_at' => $order->created_at->toIso8601String(),
        ];
    }
}
