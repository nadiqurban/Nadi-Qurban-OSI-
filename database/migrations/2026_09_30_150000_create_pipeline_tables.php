<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Core vendor fields needed by Agihan Negara; the full vendor module (PO, payments,
        // reports, ranking) extends this table in Phase 6.
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();              // SP 001 (editable)
            $table->string('name', 150);
            $table->string('company', 150)->nullable();
            $table->string('supplier', 150)->nullable();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('level', 20)->nullable();           // platinum / gold / silver / bronze
            $table->string('status', 20)->default('aktif')->index();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->json('animals')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 60)->nullable();
            $table->string('bank_holder', 150)->nullable();
            $table->string('swift', 20)->nullable();
            $table->unsignedTinyInteger('rank')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            // Vendor PIC users are linked to their vendor (sees only their own orders).
            $table->foreignId('vendor_id')->nullable()->after('status')->constrained()->nullOnDelete();
        });

        Schema::create('akad_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('method', 20);
            $table->foreignId('witness_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('consented')->default(true);
            $table->timestamp('recorded_at');
            $table->timestamps();
        });

        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('vendor_id');
        });

        Schema::create('execution_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->index();
            $table->text('notes')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('courier', 20);
            $table->string('post_type', 20);
            $table->string('consignment_no', 40)->unique();
            $table->string('recipient_name', 150);
            $table->string('phone', 30)->nullable();
            $table->string('address', 500);
            $table->string('postcode', 10)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 40)->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamp('delivered_at')->nullable();      // courier tracking (EasyParcel, Phase 9)
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');           // bahagian / participant number
            $table->string('certificate_no', 30)->unique();     // NQ-SIJIL-2027-0248
            $table->string('recipient_name', 150);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['order_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('execution_reports');
        Schema::dropIfExists('allocations');
        Schema::dropIfExists('akad_records');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });
        Schema::dropIfExists('vendors');
    }
};
