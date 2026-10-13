<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'member_code' => 'OP-CUSTOMER-DEMO',
                'display_name' => 'Caca Ocean',
                'username' => 'oceanpaws.demo',
                'line_user_id' => 'line-oceanpaws-demo',
                'avatar_url' => 'https://images.pexels.com/photos/774909/pexels-photo-774909.jpeg?auto=compress&fit=crop&w=240&h=240',
                'email' => 'customer.demo@oceanpaws.test',
                'phone' => '081234567890',
                'address' => "Caca Ocean\nJl. Samudra Biru No. 23\nJakarta Selatan 12345",
                'notes' => 'Akun utama untuk mengecek seluruh tampilan customer.',
                'is_active' => true,
            ],
            [
                'member_code' => 'OP-CUSTOMER-ACTIVE',
                'display_name' => 'Nara WAV',
                'username' => 'nara.wav',
                'line_user_id' => 'line-oceanpaws-nara',
                'avatar_url' => 'https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg?auto=compress&fit=crop&w=240&h=240',
                'email' => null,
                'phone' => '081234567891',
                'address' => 'Bandung, Jawa Barat',
                'notes' => 'Member demo dengan pesanan aktif.',
                'is_active' => true,
            ],
            [
                'member_code' => 'OP-CUSTOMER-INACTIVE',
                'display_name' => 'Mina Archive',
                'username' => 'mina.archive',
                'line_user_id' => 'line-oceanpaws-mina',
                'avatar_url' => null,
                'email' => null,
                'phone' => '081234567892',
                'address' => 'Surabaya, Jawa Timur',
                'notes' => 'Member nonaktif untuk mengecek hasil pencarian kosong.',
                'is_active' => false,
            ],
        ];

        foreach ($members as $attributes) {
            Member::query()->updateOrCreate(
                ['member_code' => $attributes['member_code']],
                $attributes,
            );
        }

        $demoMember = Member::query()->where('member_code', 'OP-CUSTOMER-DEMO')->firstOrFail();
        User::query()->updateOrCreate(
            ['email' => 'customer.demo@oceanpaws.test'],
            [
                'member_id' => $demoMember->id,
                'name' => $demoMember->display_name,
                'username' => $demoMember->username,
                'password_set_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'customer',
                'line_user_id' => $demoMember->line_user_id,
                'avatar_url' => $demoMember->avatar_url,
                'email_verified_at' => now(),
            ],
        );
    }
}
