<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repositori Dokumen: uploaded files (own media) + auto-registered system
        // files (other models' media, or PDFs rendered on demand from a route).
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->nullable()->unique();           // dedupe for auto-registered docs, e.g. "invoice:12"
            $table->string('name', 200);
            $table->string('category', 20)->index();
            $table->string('service', 20)->nullable()->index();        // Lembu / Kambing / Unta / Aqiqah / Lain
            $table->string('source', 10)->default('upload');          // upload | system
            $table->string('extension', 10);
            $table->unsignedBigInteger('size')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media')->cascadeOnDelete();
            $table->string('route_name', 80)->nullable();
            $table->json('route_params')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
