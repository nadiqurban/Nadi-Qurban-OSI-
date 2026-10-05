<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One clean example per segment (run by `php artisan nq:sample-data`).
 * Reuses the demo seeders in "sample" mode: one Uganda vendor & product, one promo
 * code, one order waiting at each pipeline screen (Pengesahan Bayaran → Selesai),
 * one instalment plan, one PO, one invoice, one CRM lead and the document register.
 */
class SampleDataSeeder extends Seeder
{
    /** Read by the demo seeders to trim their rows to a single example. */
    public static bool $active = false;

    public function run(): void
    {
        self::$active = true;

        try {
            $this->call([
                MasterDataSeeder::class,
                DemoVendorSeeder::class,
                DemoCatalogSeeder::class,
                DemoOrderSeeder::class,
                DemoInstallmentSeeder::class,
                DemoPurchaseOrderSeeder::class,
                DemoFinanceSeeder::class,
                DemoCrmSeeder::class,
                DemoDocumentSeeder::class,
            ]);
        } finally {
            self::$active = false;
        }
    }

    /** Staff member recorded as witness/approver: demo admin, else the first Super Admin. */
    public static function staff(): ?User
    {
        return User::query()->where('email', 'nurfitri@nadiqurban.com')->first()
            ?? User::role(RoleName::SuperAdmin->value)->orderBy('id')->first();
    }
}
