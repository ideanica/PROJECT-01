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
        $email = config('seeders.demo_customer_email');
        $password = config('seeders.demo_customer_password');

        if (! filled($password)) {
            throw new RuntimeException('Atur OCEANPAWS_DEMO_CUSTOMER_PASSWORD untuk membuat akun customer demo di production.');
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info("Akun {$email} sudah ada; seeder tidak mengubah kredensial yang sudah diatur.");

            return;
        }

        DB::transaction(function () use ($email, $password): void {
            $member = Member::query()->firstOrCreate(
                ['member_code' => 'OP-CUSTOMER-DEMO'],
                [
                    'display_name' => 'OceanPaws Demo',
                    'email' => $email,
                    'username' => null,
                    'notes' => '[DEMO:CustomerDemoAccountSeeder]',
                    'is_active' => true,
                ],
            );

            if (! str_contains((string) $member->notes, '[DEMO:CustomerDemoAccountSeeder]')) {
                throw new RuntimeException('Kode member demo sudah dipakai oleh sumber data lain.');
            }

            if ($member->user()->exists()) {
                throw new RuntimeException('Member demo sudah terhubung ke akun lain; seeder tidak mengubah relasinya.');
            }

            $member->fill([
                'display_name' => 'OceanPaws Demo',
                'email' => $email,
                'username' => null,
                'is_active' => true,
            ])->save();

            $user = new User;
            $user->forceFill([
                'name' => 'OceanPaws Demo',
                'email' => $email,
                'username' => null,
                'member_id' => $member->id,
                'role' => User::ROLE_CUSTOMER,
                'password' => Hash::make($password),
                'password_set_at' => now(),
                'email_verified_at' => now(),
                'registration_pending' => false,
            ])->save();
        });

        $this->command?->info("Akun customer demo siap: {$email}");
    }
}
