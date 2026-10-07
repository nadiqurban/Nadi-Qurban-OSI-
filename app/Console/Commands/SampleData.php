<?php

namespace App\Console\Commands;

use App\Support\DashboardStats;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Clear all business data and leave one clean example per segment (or nothing, with --kosong).
 * Kept: users, roles & permissions, settings, countries & packages, API clients,
 * webhooks and the audit log (immutable).
 */
class SampleData extends Command
{
    protected $signature = 'nq:sample-data {--force : Jalankan tanpa pengesahan} {--kosong : Kosongkan sahaja, tanpa data contoh (sebelum guna sebenar)}';

    protected $description = 'Kosongkan data operasi dan masukkan 1 contoh bagi setiap segmen';

    /** Child tables first. */
    public const TABLES = [
        'order_stage_histories', 'order_participants', 'akad_records', 'allocations', 'execution_reports',
        'shipments', 'certificates', 'payment_gateway_transactions', 'installments', 'installment_plans',
        'payments', 'orders', 'vendor_reports', 'vendor_payments', 'purchase_orders', 'vendor_rank_histories',
        'invoice_payments', 'invoice_items', 'invoices', 'quotations', 'lead_activities', 'leads',
        'documents', 'report_exports', 'customers', 'promo_codes', 'products', 'vendors', 'sequences',
        'notifications', 'agent_clicks',
    ];

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Persekitaran produksi: guna --force jika anda pasti.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Semua tempahan, pelanggan, vendor, invois, CRM & dokumen akan dipadam. Teruskan?')) {
            return self::FAILURE;
        }

        // Files first (bukti bayaran, laporan pelaksanaan, resit vendor, dokumen).
        Media::query()->where('model_type', '!=', 'App\\Models\\User')->each(fn (Media $m) => $m->delete());

        // Vendor PIC accounts are kept; the sample vendor seeder re-links the demo one.
        DB::table('users')->whereNotNull('vendor_id')->update(['vendor_id' => null]);

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Data operasi dikosongkan.');

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
}
