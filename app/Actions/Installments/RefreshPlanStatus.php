<?php

namespace App\Actions\Installments;

use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Models\InstallmentPlan;

/**
 * Derives the plan status from its instalments: all paid → Selesai; any unpaid
 * instalment past its due date → Lewat Bayar; otherwise Berjalan. Batal is sticky.
 */
class RefreshPlanStatus
{
    public function handle(InstallmentPlan $plan): InstallmentPlanStatus
    {
        if ($plan->status === InstallmentPlanStatus::Cancelled) {
            return $plan->status;
        }

        $plan->load('installments');

        $status = match (true) {
            $plan->isFullyPaid() => InstallmentPlanStatus::Completed,
            $plan->installments->contains(fn ($i) => $i->status === InstallmentStatus::Unpaid && $i->due_date->lt(today())) => InstallmentPlanStatus::Late,
            default => InstallmentPlanStatus::Ongoing,
        };

        if ($status !== $plan->status) {
            $plan->forceFill(['status' => $status])->save();
        }

        return $status;
    }
}
