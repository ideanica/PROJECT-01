<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        // Existing credentials stay admin when rolling back this data repair.
    }
};
