<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 6 extends the Phase 4 vendors table with the full profile.
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('vendor_no', 20)->nullable()->unique()->after('code');   // VND-2010
            $table->string('pic_name', 120)->nullable()->after('email');
            $table->string('bank_address', 300)->nullable()->after('swift');
        });

        Schema::create('vendor_rank_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('from_rank')->nullable();
            $table->unsignedTinyInteger('to_rank');
            $table->string('note', 200)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_no', 30)->unique();                   // NQ-PO-2027-0148
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('service', 20);
            $table->string('animal_label', 60);                      // "Lembu (1 bhg)"
            $table->unsignedInteger('quantity');
            $table->string('currency', 3)->default('RM');            // RM / USD
            $table->decimal('exchange_rate', 10, 4)->default(1);     // RM per 1 unit of currency (snapshot)
            $table->unsignedBigInteger('unit_price_minor');          // in the PO currency (sen / cents)
            $table->unsignedBigInteger('total_minor');
            $table->unsignedBigInteger('total_rm_sen');              // for Kewangan / stats
            $table->date('implementation_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_terms', 120)->nullable();
            $table->string('reference', 60)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_sen');
            $table->string('status', 20)->default('pending')->index(); // pending / processing / completed
            $table->date('payment_date')->nullable();
            $table->string('approved_by_name', 120)->nullable();       // "Approved By (HQ PIC)"
            $table->string('bank', 60)->nullable();
            $table->string('reference', 60)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft')->index();   // draft / submitted / verified / revision
            $table->string('revision_note', 300)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_reports');
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('vendor_rank_histories');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique(['vendor_no']);
            $table->dropColumn(['vendor_no', 'pic_name', 'bank_address']);
        });
    }
};
