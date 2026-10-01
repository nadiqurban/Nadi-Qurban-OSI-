<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Integrasi API: each key belongs to an API client (Sanctum tokenable).
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('environment', 10)->default('live');        // live | test
            $table->string('prefix', 24);                              // shown in the list, e.g. nq_live_88f3a1
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        // One row per /api/v1 call (usage meter + "Panggilan (30h)"); pruned after 90 days.
        Schema::create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 8);
            $table->string('path', 200);
            $table->unsignedSmallInteger('status');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->string('description', 150)->nullable();
            $table->json('events');                                    // ['payment.confirmed', …]
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->string('event', 40);
            $table->unsignedSmallInteger('http_status')->nullable();   // null = no response (timeout / DNS)
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('status', 10)->default('menunggu');         // menunggu | berjaya | gagal
            $table->string('error', 300)->nullable();
            $table->timestamps();

            $table->index(['webhook_endpoint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_requests');
        Schema::dropIfExists('api_clients');
    }
};
