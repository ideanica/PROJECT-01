<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

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

        $attributes = config('seeders.admin');
        $required = ['name', 'email', 'password'];
        $provided = collect($required)->filter(fn (string $key) => filled($attributes[$key] ?? null));

        if ($provided->isEmpty()) {
            return;
        }

        if ($provided->count() !== count($required)) {
            throw new RuntimeException('Lengkapi INITIAL_ADMIN_NAME, INITIAL_ADMIN_EMAIL, dan INITIAL_ADMIN_PASSWORD untuk membuat admin awal.');
        }

        if (User::where('email', $attributes['email'])->exists()) {
            throw new RuntimeException('Email admin awal sudah dipakai akun lain. Admin seeder tidak mengubah akun yang sudah ada.');
        }

        User::create([
            'name' => $attributes['name'],
            'username' => $attributes['username'] ?? null,
            'email' => $attributes['email'],
            'role' => User::ROLE_ADMIN,
            'password_set_at' => now(),
            'email_verified_at' => now(),
            'password' => Hash::make($attributes['password']),
        ]);
    }
}
