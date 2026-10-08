<?php

namespace App\Console\Commands;

use App\Models\ExecutionReport;
use App\Models\InstallmentPlan;
use App\Models\Payment;
use App\Models\PaymentGatewayTransaction;
use App\Support\DashboardStats;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Clear the business data and leave one clean example per segment (or nothing, with --kosong).
 *
 * Real money is never thrown away: every order / instalment plan with a successful CHIP
 * payment is kept with its customer, payment, participants, pipeline records, files and
 * the products / vendors it points to; numbering (sequences) is then kept too so new
 * numbers never collide with them.
 * Always kept: users, roles & permissions, settings, countries & packages, API clients,
 * webhooks and the audit log (immutable).
 */
class SampleData extends Command
{
    protected $signature = 'nq:sample-data {--force : Jalankan tanpa pengesahan} {--kosong : Kosongkan sahaja, tanpa data contoh (sebelum guna sebenar)}';

    protected $description = 'Kosongkan data operasi (kekalkan bayaran CHIP yang berjaya) dan masukkan 1 contoh bagi setiap segmen';

    /** Child tables first. */
    public const TABLES = [
        'order_stage_histories', 'order_participants', 'akad_records', 'allocations', 'execution_reports',
        'shipments', 'certificates', 'payment_gateway_transactions', 'installments', 'installment_plans',
        'payments', 'orders', 'vendor_reports', 'vendor_payments', 'purchase_orders', 'vendor_rank_histories',
        'invoice_payments', 'invoice_items', 'invoices', 'quotations', 'lead_activities', 'leads',
        'documents', 'report_exports', 'customers', 'promo_codes', 'products', 'vendors', 'sequences',
        'notifications', 'agent_clicks',
    ];

    /** Tables tied to a single order (deleted with the order). */
    private const ORDER_CHILDREN = [
        'order_stage_histories', 'order_participants', 'akad_records', 'allocations', 'execution_reports',
        'shipments', 'certificates', 'payments',
    ];

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Persekitaran produksi: guna --force jika anda pasti.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Semua tempahan, pelanggan, vendor, invois, CRM & dokumen akan dipadam (kecuali bayaran CHIP yang berjaya). Teruskan?')) {
            return self::FAILURE;
        }

        $keep = $this->keptRecords();
        $this->clear($keep);

        $this->info($keep['orders']->isEmpty() && $keep['plans']->isEmpty()
            ? 'Data operasi dikosongkan.'
            : "Data operasi dikosongkan. Dikekalkan (bayaran CHIP berjaya): {$keep['orders']->count()} tempahan, {$keep['plans']->count()} pelan ansuran.");

        if ($this->option('kosong')) {
            DashboardStats::flush();
            $this->info('Siap: sistem kosong dan sedia untuk data sebenar.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => SampleDataSeeder::class, '--force' => true]);
        DashboardStats::flush();

        $this->info('Siap: 1 contoh bagi setiap segmen telah dimasukkan.');

        return self::SUCCESS;
    }

    /**
     * Orders, plans and everything they reference that must survive.
     *
     * @return array{orders: Collection<int, int>, plans: Collection<int, int>, customers: Collection<int, int>, products: Collection<int, int>, vendors: Collection<int, int>}
     */
    private function keptRecords(): array
    {
        $paid = PaymentGatewayTransaction::query()->where('status', PaymentGatewayTransaction::PAID);

        $plans = (clone $paid)->whereNotNull('installment_plan_id')->pluck('installment_plan_id')->unique()->values();
        $orders = (clone $paid)->whereNotNull('order_id')->pluck('order_id')
            ->merge(DB::table('installment_plans')->whereIn('id', $plans)->whereNotNull('order_id')->pluck('order_id'))
            ->map(fn ($id) => (int) $id)->unique()->values();

        $orderRows = DB::table('orders')->whereIn('id', $orders);
        $planRows = DB::table('installment_plans')->whereIn('id', $plans);

        return [
            'orders' => $orders,
            'plans' => $plans->map(fn ($id) => (int) $id)->values(),
            'customers' => (clone $orderRows)->pluck('customer_id')->merge((clone $planRows)->pluck('customer_id'))->map(fn ($id) => (int) $id)->unique()->values(),
            'products' => (clone $orderRows)->pluck('product_id')->merge((clone $planRows)->pluck('product_id'))->filter()->map(fn ($id) => (int) $id)->unique()->values(),
            'vendors' => DB::table('allocations')->whereIn('order_id', $orders)->pluck('vendor_id')
                ->merge(DB::table('execution_reports')->whereIn('order_id', $orders)->whereNotNull('vendor_id')->pluck('vendor_id'))
                ->map(fn ($id) => (int) $id)->unique()->values(),
        ];
    }

    /** @param  array{orders: Collection<int, int>, plans: Collection<int, int>, customers: Collection<int, int>, products: Collection<int, int>, vendors: Collection<int, int>}  $keep */
    private function clear(array $keep): void
    {
        $keepsSomething = $keep['orders']->isNotEmpty() || $keep['plans']->isNotEmpty();

        // Files first, except those of kept payments / execution reports / plans.
        $keptMedia = [
            Payment::class => DB::table('payments')->whereIn('order_id', $keep['orders'])->pluck('id')->all(),
            ExecutionReport::class => DB::table('execution_reports')->whereIn('order_id', $keep['orders'])->pluck('id')->all(),
            InstallmentPlan::class => $keep['plans']->all(),
        ];
        Media::query()->where('model_type', '!=', 'App\\Models\\User')->each(function (Media $m) use ($keptMedia) {
            if (! in_array($m->model_id, $keptMedia[$m->model_type] ?? [], false)) {
                $m->delete();
            }
        });

        // Vendor PIC accounts are kept; the sample vendor seeder re-links the demo one.
        DB::table('users')->whereNotNull('vendor_id')->whereNotIn('vendor_id', $keep['vendors'])->update(['vendor_id' => null]);

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $query = DB::table($table);

                match (true) {
                    in_array($table, self::ORDER_CHILDREN, true) => $query->whereNotIn('order_id', $keep['orders'])->delete(),
                    $table === 'orders' => $query->whereNotIn('id', $keep['orders'])->delete(),
                    $table === 'installment_plans' => $query->whereNotIn('id', $keep['plans'])->delete(),
                    $table === 'installments' => $query->whereNotIn('installment_plan_id', $keep['plans'])->delete(),
                    $table === 'payment_gateway_transactions' => $query
                        ->where(fn ($q) => $q->whereNull('order_id')->orWhereNotIn('order_id', $keep['orders']))
                        ->where(fn ($q) => $q->whereNull('installment_plan_id')->orWhereNotIn('installment_plan_id', $keep['plans']))
                        ->delete(),
                    $table === 'customers' => $query->whereNotIn('id', $keep['customers'])->delete(),
                    $table === 'products' => $query->whereNotIn('id', $keep['products'])->delete(),
                    $table === 'vendors' => $query->whereNotIn('id', $keep['vendors'])->delete(),
                    $table === 'vendor_rank_histories' => $query->whereNotIn('vendor_id', $keep['vendors'])->delete(),
                    // Kept orders already hold numbers: never restart numbering then.
                    $table === 'sequences' => $keepsSomething ? 0 : $query->delete(),
                    default => $query->delete(),
                };
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
