<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pendaftaran Ejen (public form): null = approved / added by HQ, "menunggu" = awaiting HQ, "ditolak" = rejected. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('registration_status', 12)->nullable()->index()->after('bank_account_no');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('registration_status');
        });
    }
};
