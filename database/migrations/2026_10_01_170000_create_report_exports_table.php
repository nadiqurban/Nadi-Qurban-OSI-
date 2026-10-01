<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pusat Laporan: one row per generated report (queued job → private file).
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('name', 150);
            $table->string('format', 5);                              // pdf | xlsx | csv
            $table->json('filters')->nullable();                      // {from, to, countries[]}
            $table->string('status', 12)->index();                   // diproses | siap | gagal
            $table->string('path', 255)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('error', 500)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
