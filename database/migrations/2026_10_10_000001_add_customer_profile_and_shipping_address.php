<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('line_username', 100)->nullable();
        });
        Schema::table('member_orders', function (Blueprint $table) {
            $table->text('shipping_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('member_orders', fn (Blueprint $table) => $table->dropColumn('shipping_address'));
        Schema::table('members', fn (Blueprint $table) => $table->dropColumn('line_username'));
    }
};
