<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bayaran Ansuran: one plan per future order. The order number is reserved at
        // creation; the Order itself is created by "Hantar" once every month is paid.
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 150);
            $table->string('service', 20);
            $table->string('animal', 20);
            $table->string('package_name', 50);
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->json('participant_names')->nullable();
            $table->unsignedSmallInteger('year');
            $table->date('implementation_date')->nullable();
            $table->unsignedBigInteger('unit_price_sen');
            $table->unsignedBigInteger('subtotal_sen');
            $table->unsignedBigInteger('discount_sen')->default(0);
            $table->unsignedBigInteger('total_sen');                 // subtotal − discount
            $table->unsignedBigInteger('deposit_sen')->default(0);
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promo_code', 40)->nullable();
            $table->unsignedTinyInteger('months');                   // 3 / 6 / 9 / 12
            $table->unsignedBigInteger('monthly_sen');
            $table->string('payment_method', 20);                    // fpx_auto / kad / manual
            $table->string('status', 20)->default('berjalan')->index();
            $table->string('cancel_reason', 300)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('pay_token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('seq');
            $table->date('due_date')->index();
            $table->unsignedBigInteger('amount_sen');
            $table->string('status', 20)->default('belum');          // belum / dibayar
            $table->timestamp('paid_at')->nullable();
            $table->string('method', 60)->nullable();
            $table->string('gateway_ref', 80)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reminded_before_at')->nullable();    // H-3
            $table->timestamp('reminded_after_at')->nullable();     // H+1
            $table->timestamps();

            $table->unique(['installment_plan_id', 'seq']);
        });

        Schema::create('payment_gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 20)->default('chip');
            $table->string('reference', 40)->unique();              // NQPAY000123
            $table->string('purchase_id', 64)->nullable()->unique();
            $table->foreignId('installment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->json('installment_ids');
            $table->unsignedBigInteger('amount_sen');
            $table->string('method', 30)->nullable();               // chosen in the portal
            $table->string('status', 20)->default('created');       // created / paid / failed / cancelled
            $table->string('checkout_url', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
        Schema::dropIfExists('installments');
        Schema::dropIfExists('installment_plans');
    }
};
