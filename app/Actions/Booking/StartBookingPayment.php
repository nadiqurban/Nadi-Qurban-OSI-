<?php

namespace App\Actions\Booking;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\PaymentGatewayTransaction;
use App\Services\Chip\ChipGateway;
use App\Support\PaymentGateways;
use App\Support\Sequence;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * "Bayar dengan CHIP": a CHIP Collect purchase for a public booking that is still
 * waiting for payment; returns the transaction with its checkout URL.
 */
class StartBookingPayment
{
    public function __construct(private readonly ChipGateway $chip) {}

    public function handle(Order $order): PaymentGatewayTransaction
    {
        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw ValidationException::withMessages(['pay' => 'Tempahan ini tidak menunggu bayaran.']);
        }

        if (! $this->chip->isConfigured() || ! app(PaymentGateways::class)->enabled('chip')) {
            throw ValidationException::withMessages(['pay' => 'Gerbang pembayaran belum tersedia. Sila pilih Pindahan Bank atau hubungi Nadi Qurban.']);
        }

        $tx = PaymentGatewayTransaction::query()->create([
            'gateway' => 'chip',
            'reference' => sprintf('NQPAY%06d', Sequence::next('gateway-payment', 100250)),
            'order_id' => $order->id,
            'installment_ids' => [],
            'amount_sen' => $order->total_sen,
            'status' => PaymentGatewayTransaction::CREATED,
        ]);

        $customer = $order->customer;
        $return = route('booking.receipt', ['token' => $order->tracking_token, 'tx' => $tx->reference]);

        try {
            $purchase = $this->chip->createPurchase([
                'client' => array_filter([
                    'email' => $customer->email ?: 'tiada-emel@nadiqurban.com',
                    'full_name' => mb_substr($customer->name, 0, 128),
                    'phone' => '+'.preg_replace('/^0/', '60', (string) preg_replace('/\D+/', '', $customer->phone)),
                ]),
                'purchase' => [
                    'currency' => 'MYR',
                    'products' => [[
                        'name' => mb_substr("{$order->product_name} — {$order->package_name} · {$order->order_no}", 0, 256),
                        'price' => $order->total_sen,
                        'quantity' => '1',
                    ]],
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'notes' => "{$order->quantity} × {$order->product_name}",
                ],
                'reference' => $tx->reference,
                'success_callback' => route('webhooks.chip'),
                'success_redirect' => $return,
                'failure_redirect' => $return,
                'cancel_redirect' => route('booking.receipt', $order->tracking_token),
                'creator_agent' => 'NadiQurbanOSI/1.0',
                'platform' => 'web',
            ]);
        } catch (RuntimeException $e) {
            $tx->forceFill(['status' => PaymentGatewayTransaction::FAILED, 'payload' => ['error' => $e->getMessage()]])->save();

            throw ValidationException::withMessages(['pay' => 'Tidak dapat menyambung ke gerbang pembayaran. Sila cuba lagi.']);
        }

        $tx->forceFill(['purchase_id' => $purchase['id'] ?? null, 'checkout_url' => (string) ($purchase['checkout_url'] ?? '')])->save();

        return $tx;
    }
}
