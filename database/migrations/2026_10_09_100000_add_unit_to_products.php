<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit', 12)->default('bahagian')->after('stock');   // Jenis Kuantiti: bahagian / ekor
        });

        DB::table('products')->where('animal', 'kambing')->update(['unit' => 'ekor']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
