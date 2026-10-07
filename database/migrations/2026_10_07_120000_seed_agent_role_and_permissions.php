<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Phase 12 on existing installs: add the "Ejen" role and the Pengurusan Ejen
 * permissions (granted to existing roles at their default level). Idempotent —
 * edits made in Matriks Kebenaran are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
    }

    public function down(): void
    {
        // Roles/permissions are left in place; removing them could lock users out.
    }
};
