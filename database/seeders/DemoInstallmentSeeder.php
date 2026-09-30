<?php

namespace Database\Seeders;

use App\Actions\Installments\CancelPlans;
use App\Actions\Installments\CreatePlan;
use App\Actions\Installments\RefreshPlanStatus;
use App\Actions\Installments\SendPlanToOrder;
use App\Enums\InstallmentStatus;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local/demo only: the plans from Bayaran Ansuran.dc.html at different points —
 * on track, late, fully paid (one already sent to Pengesahan Bayaran) and cancelled.
 * Must run after DemoOrderSeeder (shares the order number sequence).
 */
class DemoInstallmentSeeder extends Seeder
{
    public function run(CreatePlan $create, RefreshPlanStatus $refresh): void
    {
        if (InstallmentPlan::query()->exists()) {
            return;
        }

        $admin = User::query()->where('email', 'nurfitri@nadiqurban.com')->first();

        // [name, phone, email, product, qty, months, deposit RM, paid months, start (months ago), address, postcode, city, state, method, extra names]
        $rows = [
            ['Ahmad Zaki bin Hassan', '012-3345671', 'ahmad.zaki@gmail.com', 'Qurban Lembu Uganda', 1, 6, 0, 4, 3, 'No. 12, Jalan Melati 3, Taman Sri Indah', '40150', 'Shah Alam', 'Selangor', 'fpx_auto', []],
            ['Nurul Ain binti Rahman', '013-9921044', 'nurulain.r@gmail.com', 'Qurban Kambing Nigeria', 1, 3, 0, 2, 1, 'No. 8, Lorong Kenanga 2', '43000', 'Kajang', 'Selangor', 'fpx_auto', []],
            ['Mohd Firdaus bin Omar', '019-2345671', 'firdaus.omar@gmail.com', 'Qurban Unta Somalia', 1, 6, 0, 1, 3, 'No. 45, Jalan Damai 2', '68000', 'Ampang', 'Selangor', 'kad', []],
            ['Iskandar bin Yusof', '014-8890213', 'iskandar.y@gmail.com', 'Qurban Lembu Uganda', 1, 6, 500, 6, 5, 'No. 20, Persiaran Kayangan', '40000', 'Shah Alam', 'Selangor', 'manual', []],
            ['Faridah binti Omar', '011-2245778', 'faridah.omar@gmail.com', 'Aqiqah Kambing Malaysia', 2, 3, 0, 3, 2, 'No. 3, Jalan Seri Impian', '81100', 'Johor Bahru', 'Johor', 'fpx_auto', ['Muhammad Aiman bin Ahmad']],
            ['Sofea binti Kamal', '017-7781220', 'sofea.kamal@gmail.com', 'Qurban Lembu Uganda', 1, 6, 0, 2, 4, 'No. 77, Jalan Kenari 5', '47100', 'Puchong', 'Selangor', 'fpx_auto', []],
            ['Kamarul bin Zainal', '016-4412098', 'kamarul.z@gmail.com', 'Qurban Kambing Nigeria', 1, 6, 0, 1, 1, 'No. 5, Jalan Cempaka 9', '08000', 'Sungai Petani', 'Kedah', 'fpx_auto', []],
        ];

        foreach ($rows as [$name, $phone, $email, $productName, $qty, $months, $deposit, $paid, $monthsAgo, $address, $postcode, $city, $state, $method, $names]) {
            $plan = $create->handle(
                compact('name', 'phone', 'email', 'address', 'postcode', 'city', 'state'),
                [
                    'product_id' => (int) Product::query()->where('name', $productName)->value('id'),
                    'quantity' => $qty,
                    'year' => 2027,
                    'implementation_date' => '2027-06-18',
                    'months' => $months,
                    'deposit_sen' => $deposit * 100,
                    'payment_method' => $method,
                    'start_date' => today()->subMonthsNoOverflow($monthsAgo)->toDateString(),
                ],
                array_merge([$name], $names),
                null,
                $admin,
            );

            foreach ($plan->installments()->where('seq', '<=', $paid)->get() as $i) {
                $gateway = $i->seq % 2 === 0;
                $i->forceFill([
                    'status' => InstallmentStatus::Paid,
                    'paid_at' => $i->due_date->copy()->setTime(10, 15),
                    'method' => $gateway ? 'CHIP · FPX Online Banking' : 'Perbankan Internet',
                    'gateway_ref' => $gateway ? sprintf('NQPAY%06d', 100100 + $plan->id * 10 + $i->seq) : null,
                    'confirmed_by' => $gateway ? null : $admin?->id,
                ])->save();
            }

            $refresh->handle($plan);
        }

        if ($admin) {
            // Faridah's plan is complete and already sent to Pengesahan Bayaran; Kamarul's is cancelled.
            app(SendPlanToOrder::class)->handle(InstallmentPlan::query()->whereHas('customer', fn ($q) => $q->where('name', 'Faridah binti Omar'))->firstOrFail(), $admin);
            app(CancelPlans::class)->cancel(
                InstallmentPlan::query()->whereHas('customer', fn ($q) => $q->where('name', 'Kamarul bin Zainal'))->pluck('id')->all(),
                'Pelanggan menarik diri',
                $admin,
            );
        }
    }
}
