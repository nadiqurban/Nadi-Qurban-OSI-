<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 5)->default('light')->after('status');      // light | dark (global toggle)
            $table->json('dashboard_collapsed')->nullable()->after('theme');    // card keys hidden on the dashboard
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'dashboard_collapsed']);
        });
    }
};
