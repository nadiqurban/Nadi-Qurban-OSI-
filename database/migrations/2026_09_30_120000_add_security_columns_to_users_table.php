<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('position', 100)->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('position');
            $table->string('status', 20)->default('aktif')->index()->after('avatar_path');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->unsignedTinyInteger('failed_login_attempts')->default(0)->after('must_change_password');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_seen_at')->nullable()->after('locked_until');
            $table->timestamp('password_changed_at')->nullable()->after('last_seen_at');
            $table->text('two_factor_secret')->nullable()->after('password_changed_at');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'position', 'avatar_path', 'status', 'must_change_password', 'failed_login_attempts',
                'locked_until', 'last_seen_at', 'password_changed_at', 'two_factor_secret', 'two_factor_confirmed_at',
            ]);
        });
    }
};
