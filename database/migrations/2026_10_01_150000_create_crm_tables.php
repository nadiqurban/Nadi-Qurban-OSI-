<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_no', 20)->unique();                   // LEAD-5040
            $table->string('name', 150);
            $table->string('company', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('service', 20);                             // Qurban / Aqiqah / Dam / Nazar Haiwan / Korporat
            $table->string('package', 30)->nullable();
            $table->unsignedBigInteger('value_sen')->default(0);
            $table->string('stage', 20)->index();
            $table->unsignedInteger('position')->default(0);          // order within the Kanban column
            $table->string('source', 60)->nullable();
            $table->unsignedSmallInteger('participants')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'position']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                                // call / email / whatsapp / note / stage / created / done / order
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_label', 80)->nullable();             // "Auto-CRM", "Sistem"
            $table->timestamp('created_at')->useCurrent();

            $table->index(['lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
    }
};
