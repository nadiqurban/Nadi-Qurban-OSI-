<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('guard_name');
            $table->string('icon', 50)->nullable()->after('description');
            $table->string('tone', 20)->nullable()->after('icon');
            $table->unsignedSmallInteger('sort')->default(0)->after('tone');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'icon', 'tone', 'sort']);
        });
    }
};
