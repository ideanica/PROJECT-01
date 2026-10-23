<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->boolean('customer_submitted')->default(false);
            $table->uuid('submission_key')->nullable()->unique();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropUnique(['submission_key']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['customer_submitted', 'submission_key', 'reviewed_by', 'reviewed_at', 'review_notes']);
        });
    }
};
