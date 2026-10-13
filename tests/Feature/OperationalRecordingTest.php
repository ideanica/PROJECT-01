<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ShipmentGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_order_for_active_member_without_user_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $member = Member::factory()->create(['is_active' => true]);
        $batch = Batch::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.member-orders.store'), [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'go_number' => 'GO-4',
            'items' => [[
                'item_name' => 'Wish Doll',
                'variant' => 'Ryo',
                'quantity' => 1,
                'unit_price' => 400000,
            ]],
        ]);

        $response->assertStatus(303);
        $this->assertDatabaseHas('member_orders', [
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'go_number' => 'GO-4',
        ]);
    }

    public function test_registration_claim_reuses_existing_member_and_keeps_history_owner(): void
    {
        $member = Member::factory()->create([
            'display_name' => 'Legacy Buyer',
            'username' => 'legacy.buyer',
            'email' => 'legacy@example.com',
            'is_active' => true,
        ]);
        $batch = Batch::factory()->create();
        $order = MemberOrder::factory()->create(['member_id' => $member->id, 'batch_id' => $batch->id]);

        $response = $this->post(route('register'), [
            'name' => 'Legacy Buyer',
            'username' => 'legacy.buyer',
            'email' => 'legacy@example.com',
            'password' => 'OceanPaws!2026',
            'password_confirmation' => 'OceanPaws!2026',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('members', 1);

        $user = User::where('username', 'legacy.buyer')->firstOrFail();
        $this->assertSame($member->id, $user->member_id);
        $this->assertTrue($user->registration_pending);
        $this->assertSame($member->id, $order->fresh()->member_id);
    }

    public function test_payment_ledger_calculates_paid_and_remaining_amounts(): void
    {
        $order = MemberOrder::factory()->create(['total_amount' => 550000]);

        OrderPayment::create([
            'member_order_id' => $order->id,
            'type' => 'dp',
            'amount' => 200000,
            'status' => 'verified',
            'paid_at' => now(),
        ]);
        OrderPayment::create([
            'member_order_id' => $order->id,
            'type' => 'installment',
            'amount' => 150000,
            'status' => 'verified',
            'paid_at' => now(),
        ]);
        OrderPayment::create([
            'member_order_id' => $order->id,
            'type' => 'settlement',
            'amount' => 200000,
            'status' => 'pending',
        ]);

        $order->refresh();
        $this->assertSame(350000.0, $order->paid_amount);
        $this->assertSame(200000.0, $order->remaining_amount);
        $this->assertSame('partial', $order->ledger_payment_state);
    }

    public function test_order_item_can_be_assigned_to_shipment_group_from_same_batch(): void
    {
        $batch = Batch::factory()->create();
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id]);
        $shipment = ShipmentGroup::create([
            'batch_id' => $batch->id,
            'name' => 'EMS Batch 2',
            'method' => 'ems',
            'tracking_number' => '773405009988015',
        ]);

        $item = OrderItem::factory()->create([
            'member_order_id' => $order->id,
            'shipment_group_id' => $shipment->id,
        ]);

        $this->assertSame($shipment->id, $item->shipmentGroup->id);
        $this->assertSame($batch->id, $shipment->batch->id);
    }
}
