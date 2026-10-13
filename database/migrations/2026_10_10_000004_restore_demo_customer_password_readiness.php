<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $user = DB::table('users')->where('email', 'customer.demo@oceanpaws.test')
            ->where('role', 'customer')->whereNull('password_set_at')->first();
        if (! $user || ! DB::table('members')->where('id', $user->member_id)->where('member_code', 'OP-CUSTOMER-DEMO')->exists()) {
            return;
        }

        // The demo seeder provides a real password despite its synthetic LINE identifier.
        if (Hash::check('password', $user->password)) {
            DB::table('users')->where('id', $user->id)->update(['password_set_at' => now()]);
        }
    }

    public function down(): void
    {
        // Restoring login readiness must not revoke existing customer credentials.
    }
};
