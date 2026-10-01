<?php

namespace App\Actions\Installments;

use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PortalPayMethod;
use App\Models\InstallmentPlan;
use App\Models\PaymentGatewayTransaction;
use App\Services\Chip\ChipGateway;
use App\Support\PaymentGateways;
use App\Support\Sequence;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Portal "Bayar RM X Sekarang": creates a CHIP Collect purchase for the chosen unpaid
 * months (or the next one) and returns the transaction with its checkout URL.
 */
class StartPortalPayment
{
    public function __construct(private readonly ChipGateway $chip) {}

    /** @param  list<int>  $seqs */
    public function handle(InstallmentPlan $plan, array $seqs, PortalPayMethod $method): PaymentGatewayTransaction
    {
        if (in_array($plan->status, [InstallmentPlanStatus::Cancelled, InstallmentPlanStatus::Completed], true) || $plan->sent_at) {
            throw ValidationException::withMessages(['pay' => 'Pelan ini tidak menerima bayaran.']);
        }

        if (! $this->chip->isConfigured() || ! app(PaymentGateways::class)->enabled('chip')) {
            throw ValidationException::withMessages(['pay' => 'Gerbang pembayaran belum tersedia. Sila hubungi Nadi Qurban.']);
        }

        $unpaid = $plan->installments()->where('status', InstallmentStatus::Unpaid)->orderBy('seq')->get();
        $chosen = $seqs === [] ? $unpaid->take(1) : $unpaid->whereIn('seq', $seqs)->values();

        if ($chosen->isEmpty()) {
            throw ValidationException::withMessages(['pay' => 'Pilih sekurang-kurangnya satu ansuran untuk dibayar.']);
        }

        $tx = PaymentGatewayTransaction::query()->create([
            'gateway' => 'chip',
            'reference' => sprintf('NQPAY%06d', Sequence::next('gateway-payment', 100250)),
            'installment_plan_id' => $plan->id,
            'installment_ids' => $chosen->modelKeys(),
            'amount_sen' => (int) $chosen->sum('amount_sen'),
            'method' => $method,
            'status' => PaymentGatewayTransaction::CREATED,
        ]);

        $customer = $plan->customer;
        $return = route('installments.portal', ['token' => $plan->pay_token, 'tx' => $tx->reference]);

        try {
            $purchase = $this->chip->createPurchase([
                'client' => array_filter([
                    'email' => $customer->email ?: 'tiada-emel@nadiqurban.com',
                    'full_name' => mb_substr($customer->name, 0, 128),
                    'phone' => '+'.preg_replace('/^0/', '60', (string) preg_replace('/\D+/', '', $customer->phone)),
                ]),
                'purchase' => [
                    'currency' => 'MYR',
                    'products' => $chosen->map(fn ($i) => [
                        'name' => "Ansuran ke-{$i->seq} · {$plan->order_no}",
                        'price' => $i->amount_sen,
                        'quantity' => '1',
                    ])->values()->all(),
                    'timezone' => 'Asia/Kuala_Lumpur',
                    'notes' => "{$plan->ibadahLabel()} · {$plan->package_name}",
                ],
                'reference' => $tx->reference,
                'payment_method_whitelist' => $method->chipCodes(),
                'success_callback' => route('webhooks.chip'),
                'success_redirect' => $return,
                'failure_redirect' => $return,
                'cancel_redirect' => route('installments.portal', $plan->pay_token),
                'creator_agent' => 'NadiQurbanOSI/1.0',
                'platform' => 'web',
            ]);
        } catch (RuntimeException $e) {
            $tx->forceFill(['status' => PaymentGatewayTransaction::FAILED, 'payload' => ['error' => $e->getMessage()]])->save();

            throw ValidationException::withMessages(['pay' => 'Tidak dapat menyambung ke gerbang pembayaran. Sila cuba lagi.']);
        }

        $checkout = (string) ($purchase['checkout_url'] ?? '');
        $separator = str_contains($checkout, '?') ? '&' : '?';

        $tx->forceFill([
            'purchase_id' => $purchase['id'] ?? null,
            'checkout_url' => $checkout.$separator.'preferred='.$method->chipCodes()[0],
        ])->save();

        return $tx;
    }
}
