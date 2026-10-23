<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->decimal('dp_percentage', 5, 2)->default(50);
            $table->timestamps();
        });
        DB::table('payment_settings')->insert(['id' => 1, 'dp_percentage' => 50, 'created_at' => now(), 'updated_at' => now()]);
        Schema::table('member_orders', function (Blueprint $table) {
            $table->decimal('dp_percentage', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('member_orders', fn (Blueprint $table) => $table->dropColumn('dp_percentage'));
        Schema::dropIfExists('payment_settings');
    }
};
