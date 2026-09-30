<?php

namespace App\Actions\Installments;

use App\Enums\InstallmentPlanStatus;
use App\Enums\Severity;
use App\Models\InstallmentPlan;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

/** "Batal" (bulk, with a reason) and "Pulih" for instalment plans. */
class CancelPlans
{
    public function __construct(private readonly RefreshPlanStatus $refresh) {}

    /** @param  list<int>  $ids */
    public function cancel(array $ids, string $reason, User $actor): int
    {
        return DB::transaction(function () use ($ids, $reason, $actor) {
            $plans = InstallmentPlan::query()->whereIn('id', $ids)->whereNull('sent_at')
                ->where('status', '!=', InstallmentPlanStatus::Cancelled)->get();

            foreach ($plans as $plan) {
                $plan->forceFill(['status' => InstallmentPlanStatus::Cancelled, 'cancel_reason' => $reason, 'cancelled_at' => now()])->save();
                Audit::log('installment.cancelled', "Pelan ansuran {$plan->order_no} dibatalkan", $plan, Severity::Warning, ['reason' => $reason], $actor, 'installments');
            }

            return $plans->count();
        });
    }

    public function restore(InstallmentPlan $plan, User $actor): void
    {
        if ($plan->status !== InstallmentPlanStatus::Cancelled) {
            return;
        }

        $plan->forceFill(['status' => InstallmentPlanStatus::Ongoing, 'cancel_reason' => null, 'cancelled_at' => null])->save();
        $this->refresh->handle($plan);
        Audit::log('installment.restored', "Pelan ansuran {$plan->order_no} dipulihkan", $plan, Severity::Info, [], $actor, 'installments');
    }
}
