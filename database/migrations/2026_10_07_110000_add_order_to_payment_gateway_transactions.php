<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tempahan Awam pays through CHIP too: a gateway transaction belongs to a plan (ansuran) or an order. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateway_transactions', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('installment_plan_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateway_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
