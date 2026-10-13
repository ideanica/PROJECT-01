<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('method', 40)->default('other')->index();
            $table->string('tracking_number')->nullable()->index();
            $table->string('shipment_code')->nullable()->index();
            $table->foreignId('current_status_id')->nullable()->constrained('order_statuses')->nullOnDelete();
            $table->string('details_url', 2048)->nullable();
            $table->date('last_co_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'method']);
        });

        Schema::table('member_orders', function (Blueprint $table) {
            $table->string('go_number', 50)->nullable()->after('batch_id')->index();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('shipment_group_id')
                ->nullable()
                ->after('product_id')
                ->constrained('shipment_groups')
                ->nullOnDelete();
        });

        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->default('other')->index();
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('verified')->index();
            $table->string('proof_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['member_order_id', 'status']);
        });

        if (Schema::hasColumn('member_orders', 'payment_amount')) {
            DB::table('member_orders')
                ->whereNotNull('payment_amount')
                ->where('payment_amount', '>', 0)
                ->orderBy('id')
                ->chunkById(200, function ($orders): void {
                    foreach ($orders as $order) {
                        DB::table('order_payments')->insert([
                            'member_order_id' => $order->id,
                            'type' => in_array($order->payment_type ?? null, ['dp', 'full'], true)
                                ? (($order->payment_type ?? null) === 'full' ? 'settlement' : 'dp')
                                : 'other',
                            'amount' => $order->payment_amount,
                            'status' => 'verified',
                            'proof_path' => $order->payment_proof_path ?? null,
                            'paid_at' => $order->payment_submitted_at ?? $order->updated_at ?? now(),
                            'notes' => 'Backfill dari kolom pembayaran legacy.',
                            'created_by' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shipment_group_id');
        });

        Schema::table('member_orders', function (Blueprint $table) {
            $table->dropColumn('go_number');
        });

        Schema::dropIfExists('shipment_groups');
    }
};
