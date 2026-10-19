<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->decimal('ems_tax_amount', 14, 2)->nullable();
            $table->date('ems_tax_due_date')->nullable();
            $table->text('ems_tax_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn(['ems_tax_amount', 'ems_tax_due_date', 'ems_tax_notes']);
        });
    }
};
