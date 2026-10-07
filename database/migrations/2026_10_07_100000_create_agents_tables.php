<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12 — Ejen (Pengurusan Ejen.dc.html / Portal Ejen.dc.html).
 * An agent is a User with role "Ejen" plus this profile; orders remember the agent
 * whose link brought them and snapshot the commission (product commission × qty).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 12)->unique();                 // ID Ejen, cth. AZ01
            $table->string('slug', 80)->unique();                 // /e/aiman-zulkifli
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('district', 80)->nullable();
            $table->string('state', 40)->nullable();
            $table->string('bank_name', 60)->nullable();
            $table->string('bank_account_name', 120)->nullable();
            $table->string('bank_account_no', 40)->nullable();
            $table->timestamps();
        });

        // "Klik Link": one row per agent per day, incremented on each new visit.
        Schema::create('agent_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->unique(['agent_id', 'date']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->unsignedBigInteger('commission_sen')->default(0)->after('agent_id');
            $table->string('source', 12)->default('staff')->after('commission_sen');   // staff / public
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
            $table->dropColumn(['commission_sen', 'source']);
        });
        Schema::dropIfExists('agent_clicks');
        Schema::dropIfExists('agents');
    }
};
