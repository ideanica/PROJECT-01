<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class CustomerDemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $username = 'oceanpaws.demo';
        $email = 'customer.demo@oceanpaws.test';
        $password = config('seeders.demo_customer_password');

        if (! filled($password)) {
            throw new RuntimeException('Atur OCEANPAWS_DEMO_CUSTOMER_PASSWORD untuk membuat akun customer demo.');
        }

        if (User::query()->where('username', $username)->exists()) {
            $this->command?->info("Akun {$username} sudah ada; tidak ada data yang diubah.");

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new RuntimeException("Email {$email} sudah dipakai akun lain; seeder tidak mengubah akun itu.");
        }

        DB::transaction(function () use ($username, $email, $password): void {
            $member = Member::query()
                ->where(fn ($query) => $query->where('username', $username)->orWhere('email', $email))
                ->lockForUpdate()
                ->first();

            if ($member && (! $member->is_active || $member->user()->exists())) {
                throw new RuntimeException('Data buyer yang cocok sudah terhubung atau tidak aktif; seeder tidak mengubahnya.');
            }

            if (! $member) {
                $member = Member::query()->create([
                    'member_code' => 'OP-CUSTOMER-DEMO',
                    'display_name' => 'OceanPaws Demo',
                    'username' => $username,
                    'email' => $email,
                    'is_active' => true,
                ]);
            }

            $user = new User([
                'name' => 'OceanPaws Demo',
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'member_id' => $member->id,
            ]);
            $user->role = User::ROLE_CUSTOMER;
            $user->email_verified_at = now();
            $user->password_set_at = now();
            $user->registration_pending = false;
            $user->save();
        });

        $this->command?->info("Akun customer {$username} berhasil dibuat.");
    }
}
