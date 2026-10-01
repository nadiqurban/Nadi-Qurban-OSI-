<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 performance: indexes for the filters/sorts used by Dashboard
 * (season + status, upcoming executions), Kewangan (due/issue dates,
 * vendor payment dates) and Dokumen (newest first).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['year', 'status', 'created_at'], 'orders_season_status_index');
            $table->index('implementation_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['status', 'due_date']);
            $table->index('issue_date');
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->index(['status', 'payment_date']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_season_status_index');
            $table->dropIndex(['implementation_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_date']);
            $table->dropIndex(['issue_date']);
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_date']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
