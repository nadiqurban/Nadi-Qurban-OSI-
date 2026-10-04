<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Production-safe: first Super Admin from env (SUPERADMIN_NAME / SUPERADMIN_EMAIL /
 * SUPERADMIN_PASSWORD). Logs straight into the portal (no forced password change).
 * Skipped when the email is not configured or the user already exists.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('nadi.superadmin.email');
        $password = config('nadi.superadmin.password');

        if (! $email || ! $password) {
            $this->command->warn('SUPERADMIN_EMAIL / SUPERADMIN_PASSWORD tidak ditetapkan — Super Admin tidak dicipta.');

            return;
        }

        $user = User::query()->firstOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'name' => config('nadi.superadmin.name'),
                'password' => $password,
                'status' => UserStatus::Active,
                'must_change_password' => false,
            ],
        );

        $user->assignRole(RoleName::SuperAdmin->value);
    }
}
