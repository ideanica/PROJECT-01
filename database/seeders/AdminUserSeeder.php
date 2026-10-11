<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            return;
        }

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'OceanPaws Admin',
                'username' => 'admin',
                'role' => User::ROLE_ADMIN,
                'password_set_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
