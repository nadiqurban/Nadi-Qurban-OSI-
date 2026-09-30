<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->unique();          // NQ-QB-LE-001248
            $table->string('tracking_no', 30)->unique();       // NQT-2027-001248 (typed by customers)
            $table->string('tracking_token', 64)->unique();    // secret link token (unmasked names)
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Product snapshot at the time of ordering (prices never change retroactively).
            $table->string('product_name', 150);
            $table->string('service', 20)->index();
            $table->string('animal', 20)->index();
            $table->string('package_name', 50);
            $table->foreignId('country_id')->constrained()->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('year')->index();
            $table->date('implementation_date')->nullable();

            $table->unsignedBigInteger('unit_price_sen');
            $table->unsignedBigInteger('subtotal_sen');
            $table->unsignedBigInteger('discount_sen')->default(0);
            $table->unsignedBigInteger('total_sen');
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promo_code', 30)->nullable();

            $table->string('payment_method', 20);
            $table->boolean('is_instalment')->default(false);
            $table->string('status', 30)->index();
            $table->string('stage', 30)->index();
            $table->timestamp('accepted_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });

        Schema::create('order_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name', 150)->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'position']);
        });

        Schema::create('order_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 30);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'stage']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);
            $table->string('channel', 50)->nullable();          // e.g. "FPX Maybank", "DuitNow QR"
            $table->string('gateway_status', 20)->nullable();  // FPX: berjaya / gagal
            $table->unsignedBigInteger('amount_sen');
            $table->timestamp('paid_at')->nullable()->index();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejection_reason', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_stage_histories');
        Schema::dropIfExists('order_participants');
        Schema::dropIfExists('orders');
    }
};
