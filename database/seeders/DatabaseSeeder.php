<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Production-safe seeders always run; demo data only outside production.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(DemoUserSeeder::class);
        }
    }
}
