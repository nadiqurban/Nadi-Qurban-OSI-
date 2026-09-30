<?php

namespace App\Actions\Installments;

use App\Enums\InstallmentStatus;
use App\Enums\Severity;
use App\Models\Installment;
use App\Models\PaymentGatewayTransaction;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Applies a (verified) CHIP Purchase to its transaction. Idempotent: callbacks,
 * webhooks and the return-page check may all deliver the same purchase.
 * paid → instalments Dibayar; error/cancelled/expired → transaction failed and the
 * instalments stay "Perlu Bayar".
 */
class ProcessChipPurchase
{
    private const FAILED_STATUSES = ['error', 'cancelled', 'expired', 'blocked'];

    public function __construct(private readonly RefreshPlanStatus $refresh) {}

    /** @param  array<string, mixed>  $purchase */
    public function handle(array $purchase): ?PaymentGatewayTransaction
    {
        $purchaseId = (string) ($purchase['id'] ?? '');

        if ($purchaseId === '') {
            return null;
        }

        return DB::transaction(function () use ($purchase, $purchaseId) {
            $tx = PaymentGatewayTransaction::query()->where('purchase_id', $purchaseId)->lockForUpdate()->first();

            if (! $tx || $tx->isPaid()) {
                return $tx;
            }

            $status = (string) ($purchase['status'] ?? '');

            if ($status === 'paid') {
                $method = 'CHIP · '.($tx->method?->shortLabel() ?? (string) data_get($purchase, 'transaction_data.payment_method', 'Online'));

                Installment::query()->whereIn('id', $tx->installment_ids)->where('status', InstallmentStatus::Unpaid)
                    ->update(['status' => InstallmentStatus::Paid, 'paid_at' => now(), 'method' => $method, 'gateway_ref' => $tx->reference]);

                $tx->forceFill(['status' => PaymentGatewayTransaction::PAID, 'paid_at' => now(), 'payload' => $this->slim($purchase)])->save();

                if ($tx->plan) {
                    $this->refresh->handle($tx->plan);
                    Audit::log('installment.gateway_paid', "Bayaran ansuran {$tx->plan->order_no} berjaya ({$tx->reference})", $tx->plan, Severity::Info,
                        ['amount' => $tx->amount_sen, 'purchase' => $purchaseId], null, 'installments');
                }
            } elseif (in_array($status, self::FAILED_STATUSES, true)) {
                $tx->forceFill(['status' => PaymentGatewayTransaction::FAILED, 'payload' => $this->slim($purchase)])->save();
            }

            return $tx;
        });
    }

    /**
     * Keep what reconciliation needs, not the whole client object.
     *
     * @param  array<string, mixed>  $purchase
     * @return array<string, mixed>
     */
    private function slim(array $purchase): array
    {
        return [
            'id' => $purchase['id'] ?? null,
            'status' => $purchase['status'] ?? null,
            'reference' => $purchase['reference'] ?? null,
            'is_test' => $purchase['is_test'] ?? null,
            'payment_method' => data_get($purchase, 'transaction_data.payment_method'),
            'payment' => $purchase['payment'] ?? null,
            'event_type' => $purchase['event_type'] ?? null,
        ];
    }
}
