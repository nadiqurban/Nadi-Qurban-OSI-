<?php

namespace App\Actions\Installments;

use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\Severity;
use App\Models\InstallmentPlan;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Progress-bar segments in Bayaran Ansuran:
 * - markReceived: "Sahkan Bayaran Diterima" on month N → months 1…N paid (manual method).
 * - undo: clicking a paid segment → keep only months 1…N paid. Gateway (CHIP)
 *   payments cannot be undone here.
 */
class SetPaidMonths
{
    public const MANUAL_METHODS = ['Perbankan Internet', 'Mesin Deposit Tunai'];

    public function __construct(private readonly RefreshPlanStatus $refresh) {}

    public function markReceived(InstallmentPlan $plan, int $month, string $method, User $actor): void
    {
        if (! in_array($method, self::MANUAL_METHODS, true)) {
            throw ValidationException::withMessages(['method' => 'Kaedah bayaran tidak sah.']);
        }

        $this->guard($plan);

        DB::transaction(function () use ($plan, $month, $method, $actor) {
            $count = $plan->installments()->where('seq', '<=', $month)->where('status', InstallmentStatus::Unpaid)
                ->update(['status' => InstallmentStatus::Paid, 'paid_at' => now(), 'method' => $method, 'confirmed_by' => $actor->id]);

            $this->refresh->handle($plan);
            Audit::log('installment.paid', "Ansuran {$plan->order_no} bulan 1–{$month} ditanda dibayar ({$count})", $plan, Severity::Info,
                ['method' => $method], $actor, 'installments');
        });
    }

    public function undo(InstallmentPlan $plan, int $keepMonths, User $actor): void
    {
        $this->guard($plan);

        DB::transaction(function () use ($plan, $keepMonths, $actor) {
            $affected = $plan->installments()->where('seq', '>', $keepMonths)->where('status', InstallmentStatus::Paid)->get();

            if ($affected->contains(fn ($i) => $i->gateway_ref !== null)) {
                throw ValidationException::withMessages(['month' => 'Ansuran yang dibayar melalui CHIP IN tidak boleh dibatalkan.']);
            }

            $plan->installments()->whereIn('id', $affected->modelKeys())
                ->update(['status' => InstallmentStatus::Unpaid, 'paid_at' => null, 'method' => null, 'confirmed_by' => null]);

            $this->refresh->handle($plan);
            Audit::log('installment.unpaid', "Tanda bayaran ansuran {$plan->order_no} dibatalkan (kekal {$keepMonths} bulan)", $plan, Severity::Warning,
                [], $actor, 'installments');
        });
    }

    private function guard(InstallmentPlan $plan): void
    {
        if ($plan->status === InstallmentPlanStatus::Cancelled || $plan->sent_at) {
            throw ValidationException::withMessages(['month' => 'Pelan ini telah dibatalkan atau dihantar.']);
        }
    }
}
