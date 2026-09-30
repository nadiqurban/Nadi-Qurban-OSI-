<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Locked counters for human-readable numbers (CUST-10240, NQ-QB-LE-001248, NQ-PO-2027-0148 …).
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name', 60)->primary();
            $table->unsignedBigInteger('next_value');
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->char('iso2', 2)->unique();
            $table->string('flag', 16)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('code', 3)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('service', 20)->index();
            $table->string('animal', 20)->index();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_sen');
            $table->unsignedInteger('stock')->default(0);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('phone', 30)->index();
            $table->string('email')->nullable()->index();
            $table->string('address', 500)->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('description', 200)->nullable();
            $table->string('type', 10);
            // Percent: whole percent (10 = 10%). Fixed: amount in sen.
            $table->unsignedBigInteger('value');
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedBigInteger('total_discount_sen')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('sequences');
    }
};
