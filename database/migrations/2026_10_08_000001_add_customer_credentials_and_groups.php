<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // SQLite must toggle foreign keys outside a transaction while rebuilding tables.
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#7c3aed');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('customer_group_id')->nullable()->constrained()->restrictOnDelete();
        });

        // Import old free-text group labels without discarding buyer membership.
        DB::table('members')->whereNotNull('group_name')->orderBy('id')->get()->each(function ($member) {
            $name = trim($member->group_name);
            if ($name === '') {
                return;
            }
            $groupId = DB::table('customer_groups')->where('name', $name)->value('id');
            $groupId ??= DB::table('customer_groups')->insertGetId([
                'name' => $name, 'color' => '#7c3aed', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('members')->where('id', $member->id)->update(['customer_group_id' => $groupId]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->timestamp('password_set_at')->nullable();
            $table->string('role', 20)->default('customer')->change();
        });

        DB::table('users')->where('role', 'member')->update(['role' => 'customer']);

        // Reserve existing buyer usernames first; never merge identities by name/email.
        DB::table('users')->orderBy('id')->get()->each(function ($user) {
            $member = $user->member_id ? DB::table('members')->find($user->member_id) : null;
            $username = $member?->username;
            if (! $username || DB::table('users')->where('username', $username)->exists()) {
                $username = null;
            }
            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
                // LINE accounts used random passwords and need an explicit admin reset.
                'password_set_at' => $user->line_user_id ? null : now(),
            ]);
        });

        DB::table('users')->whereNull('username')->orderBy('id')->get()->each(function ($user) {
            $base = $user->role === 'admin' ? 'admin-'.$user->id : 'customer-'.$user->id;
            $username = $base;
            $suffix = 2;
            while (DB::table('users')->where('username', $username)->exists()
                || DB::table('members')->where('username', $username)->exists()) {
                $username = $base.'-'.$suffix++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        DB::table('members')->whereNotNull('customer_group_id')->orderBy('id')->get()->each(function ($member) {
            DB::table('members')->where('id', $member->id)->update([
                'group_name' => DB::table('customer_groups')->where('id', $member->customer_group_id)->value('name'),
            ]);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'password_set_at']);
            $table->string('role', 20)->default('admin')->change();
        });
        Schema::table('members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_group_id');
        });
        Schema::dropIfExists('customer_groups');
    }
};
