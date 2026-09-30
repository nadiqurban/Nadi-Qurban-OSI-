<?php

namespace App\Console\Commands;

use App\Actions\Installments\RefreshPlanStatus;
use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Mail\InstallmentReminder;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Daily: marks overdue plans "Lewat Bayar" and emails reminders 3 days before
 * (H-3) and 1 day after (H+1) each due date — once per instalment.
 */
#[Signature('installments:daily')]
#[Description('Kemas kini status Lewat Bayar & hantar peringatan ansuran (H-3, H+1)')]
class InstallmentsDaily extends Command
{
    public function handle(RefreshPlanStatus $refresh): int
    {
        $active = [InstallmentPlanStatus::Ongoing, InstallmentPlanStatus::Late];
        $changed = 0;

        InstallmentPlan::query()->whereIn('status', $active)->whereNull('sent_at')->with('installments')
            ->chunkById(200, function ($plans) use ($refresh, &$changed) {
                foreach ($plans as $plan) {
                    $before = $plan->status;
                    $changed += (int) ($refresh->handle($plan) !== $before);
                }
            });

        $sent = $this->remind('before', today()->addDays(3), 'reminded_before_at')
            + $this->remind('after', today()->subDay(), 'reminded_after_at');

        $this->info("Status dikemas kini: {$changed} · Peringatan dihantar: {$sent}");

        return self::SUCCESS;
    }

    private function remind(string $when, Carbon $due, string $column): int
    {
        $count = 0;

        Installment::query()
            ->where('status', InstallmentStatus::Unpaid)
            ->whereDate('due_date', $due)
            ->whereNull($column)
            ->whereHas('plan', fn ($q) => $q->whereIn('status', [InstallmentPlanStatus::Ongoing, InstallmentPlanStatus::Late])->whereNull('sent_at'))
            ->with('plan.customer')
            ->chunkById(200, function ($installments) use ($when, $column, &$count) {
                foreach ($installments as $installment) {
                    $email = $installment->plan->customer->email;

                    if ($email) {
                        Mail::to($email)->queue(new InstallmentReminder($installment, $when));
                        $count++;
                    }

                    $installment->forceFill([$column => now()])->save();
                }
            });

        return $count;
    }
}
