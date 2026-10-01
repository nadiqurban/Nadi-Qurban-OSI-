<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 30)->unique();                // INV-2027-0891
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_no', 30)->nullable();                // as typed (may not match an order)
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_address', 300)->nullable();
            $table->json('company');                                   // editable company block snapshot
            $table->unsignedBigInteger('subtotal_sen');
            $table->unsignedBigInteger('tax_sen')->default(0);
            $table->unsignedBigInteger('total_sen');
            $table->unsignedBigInteger('paid_sen')->default(0);
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 20)->index();                     // deposit / dibayar / tertunggak / lewat
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 200);
            $table->string('detail', 200)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_sen');
            $table->unsignedBigInteger('line_total_sen');
            $table->unsignedSmallInteger('position')->default(0);
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_sen');
            $table->string('method', 40);
            $table->string('reference', 60)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no', 30)->unique();              // QUO-2027-0076
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_address', 300)->nullable();
            $table->json('company');
            $table->json('items');                                     // [{description, quantity, unit_price_sen}]
            $table->unsignedBigInteger('total_sen');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
