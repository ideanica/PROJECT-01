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
            throw new RuntimeException('Atur OCEANPAWS_DEMO_CUSTOMER_PASSWORD untuk membuat akun customer seeder di production.');
        }

        $existingUser = User::query()->where('email', $email)->first();
        if ($existingUser) {
            $member = Member::query()->find($existingUser->member_id);
            if ($member) {
                $attributes = [];
                if ($member->member_code === 'OP-CUSTOMER-DEMO') {
                    $attributes['member_code'] = 'OP-CUST-0001';
                }
                if ($member->display_name === 'OceanPaws Demo') {
                    $attributes['display_name'] = 'Nadia Putri';
                    $existingUser->update(['name' => 'Nadia Putri']);
                }
                if (str_contains((string) $member->notes, '[DEMO:CustomerDemoAccountSeeder]')) {
                    $attributes['notes'] = null;
                }
                if ($attributes) {
                    $member->update($attributes);
                }
            }
            $this->command?->info("Akun {$email} sudah ada; seeder tidak mengubah kredensial yang sudah diatur.");

            return;
        }

        DB::transaction(function () use ($email, $password): void {
            $member = Member::query()->firstOrNew(
                ['member_code' => 'OP-CUST-0001'],
                [
                    'display_name' => 'Nadia Putri',
                    'email' => $email,
                    'username' => null,
                    'notes' => null,
                    'is_active' => true,
                ],
            );

            if ($member->exists && $member->email !== $email) {
                throw new RuntimeException('Kode member sudah dipakai oleh pelanggan lain.');
            }

            if ($member->user()->exists()) {
                throw new RuntimeException('Member sudah terhubung ke akun lain; seeder tidak mengubah relasinya.');
            }

            $member->fill([
                'display_name' => 'Nadia Putri',
                'email' => $email,
                'username' => null,
                'notes' => null,
                'is_active' => true,
            ])->save();

            $user = new User;
            $user->forceFill([
                'name' => 'Nadia Putri',
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

        $this->command?->info("Akun customer siap: {$email}");
    }
}
