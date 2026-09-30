<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local/demo only: the users from Pengguna & Peranan.dc.html (+ one Operasi user).
 * Password comes from SEED_USER_PASSWORD in .env (never hard-coded).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('nadi.seed_user_password');

        if (! $password) {
            $this->command->warn('SEED_USER_PASSWORD tidak ditetapkan — pengguna demo tidak dicipta.');

            return;
        }

        // [name, email, roles, last seen (minutes ago), status, phone]
        $users = [
            ['Muhammad Nurfitkri', 'nurfitri@nadiqurban.com', [RoleName::SuperAdmin, RoleName::AdminHq], 2, UserStatus::Active, '012-3456 789'],
            ['Ahmad Sufian', 'sufian@nadiqurban.com', [RoleName::SuperAdmin], 60, UserStatus::Active, null],
            ['Fatimah Noor', 'fatimah@nadiqurban.com', [RoleName::Finance], 35, UserStatus::Active, null],
            ['Rahim Salleh', 'rahim@nadiqurban.com', [RoleName::Sales], 180, UserStatus::Active, null],
            ['Nur Hidayah', 'hidayah@nadiqurban.com', [RoleName::Sales], 60 * 24, UserStatus::Active, null],
            ['Al-Barakah Livestock', 'vendor@albarakah.ug', [RoleName::VendorPic], 300, UserStatus::Active, null],
            ['Kamarul Zaman', 'kamarul@nadiqurban.com', [RoleName::Finance], 60 * 48, UserStatus::Suspended, null],
            ['Siti Aisyah', 'aisyah@nadiqurban.com', [RoleName::AdminHq], 60 * 96, UserStatus::Suspended, null],
            ['Hakim Rosli', 'hakim@nadiqurban.com', [RoleName::Operations], 90, UserStatus::Active, null],
        ];

        foreach ($users as [$name, $email, $roles, $minutesAgo, $status, $phone]) {
            $user = User::query()->updateOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => $phone,
                'password' => $password,
                'status' => $status,
                'must_change_password' => false,
            ]);

            $user->forceFill(['last_seen_at' => now()->subMinutes($minutesAgo), 'email_verified_at' => now()])->save();
            $user->syncRoles(array_map(fn (RoleName $r) => $r->value, $roles));
        }
    }
}
