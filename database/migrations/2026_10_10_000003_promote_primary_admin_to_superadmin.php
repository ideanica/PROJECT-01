<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Bootstrap the existing seeded administrator without changing credentials.
        if (! DB::table('users')->where('role', 'superadmin')->exists()) {
            DB::table('users')->where('email', 'admin@example.com')->where('role', 'admin')->whereNull('member_id')->update(['role' => 'superadmin']);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@example.com')->where('role', 'superadmin')->whereNull('member_id')->update(['role' => 'admin']);
    }
};
