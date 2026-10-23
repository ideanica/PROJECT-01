<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderPaymentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_order_requires_and_snapshots_payment_type(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = Member::factory()->create(['is_active' => true]);
        User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'member_id' => $member->id,
            'registration_pending' => false,
            'password_set_at' => now(),
        ]);
        $batch = Batch::factory()->create();
        $product = Product::factory()->create(['default_price' => 200000]);
        $batch->products()->attach($product, ['full_price' => 200000]);

        $this->actingAs($admin)->get(route('admin.member-orders.create'))
            ->assertOk()->assertSee('name="payment_type"', false)->assertSee('DP mengikuti persentase global admin');

        $this->from(route('admin.member-orders.create'))->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '']],
        ])->assertSessionHasErrors('payment_type');

        $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'payment_type' => 'dp',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => '']],
        ])->assertRedirect();

        $order = MemberOrder::query()->whereBelongsTo($member)->whereBelongsTo($batch)->sole();
        $this->assertSame('dp', $order->payment_type);
        $this->assertSame('50.00', $order->dp_percentage);
        $this->assertSame(100000.0, $order->next_payment['amount']);

        PaymentSetting::query()->findOrFail(1)->update(['dp_percentage' => 30]);
        $this->assertSame(100000.0, $order->fresh()->next_payment['amount']);
    }

    public function test_admin_cannot_change_payment_type_after_a_payment_record_exists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = MemberOrder::factory()->create([
            'payment_type' => 'dp',
            'dp_percentage' => 40,
            'total_amount' => 100000,
        ]);
        $order->payments()->create([
            'type' => 'dp',
            'amount' => 40000,
            'status' => 'verified',
            'created_by' => $admin->id,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.member-orders.edit', $order))
            ->assertOk()->assertSee('Skema dikunci karena pesanan sudah memiliki catatan pembayaran.')
            ->assertDontSee('name="payment_type"', false);

        $this->put(route('admin.member-orders.update', $order), [
            'member_id' => $order->member_id,
            'batch_id' => $order->batch_id,
            'payment_type' => 'full',
        ])->assertSessionHasErrors('payment_type');

        $this->assertSame('dp', $order->fresh()->payment_type);
        $this->assertSame('40.00', $order->fresh()->dp_percentage);
    }

    public function test_admin_can_choose_schedule_for_a_legacy_order_before_any_payment(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = MemberOrder::factory()->create([
            'payment_type' => null,
            'dp_percentage' => null,
            'total_amount' => 100000,
        ]);
        $item = OrderItem::factory()->create([
            'member_order_id' => $order->id,
            'item_name' => 'Legacy item',
            'quantity' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        $this->actingAs($admin)->put(route('admin.member-orders.update', $order), [
            'member_id' => $order->member_id,
            'batch_id' => $order->batch_id,
            'payment_type' => 'dp',
            'items' => [[
                'id' => $item->id,
                'item_name' => $item->item_name,
                'quantity' => 1,
                'unit_price' => 100000,
            ]],
        ])->assertRedirect();

        $this->assertSame('dp', $order->fresh()->payment_type);
        $this->assertSame('50.00', $order->fresh()->dp_percentage);
        $this->assertSame(50000.0, $order->fresh()->next_payment['amount']);
    }
}
