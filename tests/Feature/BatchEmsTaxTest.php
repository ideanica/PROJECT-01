<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchEmsTaxTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Batch $batch, ?Member $member = null): array
    {
        $member ??= Member::factory()->create();
        if (! $member->user) {
            User::factory()->create(['role' => 'customer', 'member_id' => $member->id, 'registration_pending' => false]);
        }

        return ['member_id' => $member->id, 'batch_id' => $batch->id,
            'items' => [['item_name' => 'Photocard', 'quantity' => 1, 'unit_price' => 100000]]];
    }

    public function test_batch_creation_accepts_optional_tax_and_can_be_configured_later(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('admin.batches.store'), ['batch_name' => 'No tax yet'])->assertStatus(303);
        $batch = Batch::where('batch_name', 'No tax yet')->sole();
        $this->assertNull($batch->ems_tax_amount);

        $this->post(route('admin.batches.store'), ['batch_name' => 'Tax at creation', 'ems_tax_amount' => '100.01'])->assertStatus(303);
        $this->assertSame('100.01', Batch::where('batch_name', 'Tax at creation')->sole()->ems_tax_amount);

        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => '150000',
            'ems_tax_due_date' => '2026-12-01', 'ems_tax_notes' => 'Pajak impor'])->assertSessionHasNoErrors();
        $this->post(route('admin.member-orders.store'), $this->payload($batch))->assertStatus(303);
        $order = $batch->orders()->sole();
        $this->assertSame('150000.00', $order->ems_tax_amount);
        $this->assertSame('unpaid', $order->ems_tax_status);
        $this->assertSame('2026-12-01', $order->ems_tax_due_date->format('Y-m-d'));
        $this->assertSame('Pajak impor', $order->ems_tax_notes);
    }

    public function test_tax_is_split_per_customer_with_exact_total_and_recomputed_after_delete(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create(['ems_tax_amount' => '100.00']);
        foreach ([1, 10, 100] as $quantity) {
            $payload = $this->payload($batch);
            $payload['items'][0]['quantity'] = $quantity;
            $this->post(route('admin.member-orders.store'), $payload)->assertStatus(303);
        }
        $orders = $batch->orders()->orderBy('member_id')->get();
        $this->assertSame(['33.34', '33.33', '33.33'], $orders->pluck('ems_tax_amount')->all());
        $this->assertEquals(100, $orders->sum('ems_tax_amount'));
        $this->delete(route('admin.member-orders.destroy', $orders->last()))->assertStatus(303);
        $this->assertSame(['50.00', '50.00'], $batch->orders()->pluck('ems_tax_amount')->all());
    }

    public function test_moving_order_recalculates_both_batches_and_clears_old_paid_state(): void
    {
        $this->actingAs(User::factory()->create());
        $source = Batch::factory()->create(['ems_tax_amount' => 100]);
        $target = Batch::factory()->create(['ems_tax_amount' => 200]);
        $first = $this->payload($source);
        $this->post(route('admin.member-orders.store'), $first)->assertStatus(303);
        $this->post(route('admin.member-orders.store'), $this->payload($source))->assertStatus(303);
        $this->post(route('admin.member-orders.store'), $this->payload($target))->assertStatus(303);
        $order = MemberOrder::where('member_id', $first['member_id'])->sole();
        $order->update(['ems_tax_status' => 'paid']);
        $this->put(route('admin.member-orders.update', $order), array_replace($first, ['batch_id' => $target->id]))->assertStatus(303);
        $this->assertSame('100.00', $source->orders()->sole()->ems_tax_amount);
        $this->assertSame(['100.00', '100.00'], $target->orders()->pluck('ems_tax_amount')->all());
        $this->assertSame('unpaid', $order->fresh()->ems_tax_status);
    }

    public function test_payment_state_is_preserved_only_when_share_does_not_change_and_tax_can_be_cleared(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create(['ems_tax_amount' => 100]);
        $this->post(route('admin.member-orders.store'), $this->payload($batch))->assertStatus(303);
        $order = $batch->orders()->sole();
        $this->put(route('admin.batches.ems-payment', [$batch, $order]), ['ems_tax_status' => 'paid'])->assertSessionHasNoErrors();
        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => 100, 'ems_tax_notes' => 'Changed note'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->ems_tax_status);
        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => 120])->assertSessionHasNoErrors();
        $this->assertSame('unpaid', $order->fresh()->ems_tax_status);
        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => null])->assertSessionHasNoErrors();
        $this->assertNull($order->fresh()->ems_tax_amount);
        $this->assertSame('not_billed', $order->fresh()->ems_tax_status);
    }

    public function test_unapproved_unready_inactive_or_admin_accounts_cannot_be_added(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create();
        foreach ([['registration_pending' => true], ['password_set_at' => null], ['role' => 'admin']] as $state) {
            $member = Member::factory()->create();
            User::factory()->create(array_replace(['role' => 'customer', 'member_id' => $member->id], $state));
            $this->post(route('admin.member-orders.store'), $this->payload($batch, $member))->assertSessionHasErrors('member_id');
            $this->get(route('admin.member-orders.create'))->assertDontSee($member->display_name);
        }
        $member = Member::factory()->create(['is_active' => false]);
        $this->post(route('admin.member-orders.store'), $this->payload($batch, $member))->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('member_orders', 0);
    }

    public function test_existing_unlinked_order_can_be_edited_but_cannot_be_moved_to_another_batch(): void
    {
        $this->actingAs(User::factory()->create());
        $order = MemberOrder::factory()->create();
        $data = ['member_id' => $order->member_id, 'batch_id' => $order->batch_id,
            'items' => [['item_name' => 'Existing buyer item', 'quantity' => 2]]];
        $this->put(route('admin.member-orders.update', $order), $data)->assertStatus(303);
        $data['batch_id'] = Batch::factory()->create()->id;
        $this->put(route('admin.member-orders.update', $order), $data)->assertSessionHasErrors('member_id');
    }

    public function test_tax_fields_are_rejected_on_order_form_and_batch_tax_validates_amounts(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create();
        $this->post(route('admin.member-orders.store'), $this->payload($batch) + [
            'ems_tax_amount' => 100, 'ems_tax_status' => 'paid', 'ems_tax_notes' => 'Override', 'ems_tax_due_date' => '2026-12-01',
        ])->assertSessionHasErrors(['ems_tax_amount', 'ems_tax_status', 'ems_tax_notes', 'ems_tax_due_date']);
        foreach ([-1, '0.001', '1000000000000'] as $invalid) {
            $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => $invalid])->assertSessionHasErrors('ems_tax_amount');
        }
        $this->get(route('admin.member-orders.create'))->assertOk()->assertDontSee('name="ems_tax_amount"', false);
        $this->get(route('admin.batches.show', $batch))->assertOk()->assertSee('Simpan Pajak EMS');
    }

    public function test_batch_edit_recalculates_tax_and_legacy_bills_are_preserved_until_configured(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create();
        $order = MemberOrder::factory()->create(['batch_id' => $batch->id, 'ems_tax_amount' => 70, 'ems_tax_status' => 'paid']);
        $this->post(route('admin.member-orders.store'), $this->payload($batch))->assertStatus(303);
        $this->assertSame('70.00', $order->fresh()->ems_tax_amount);
        $this->put(route('admin.batches.update', $batch), ['ems_tax_amount' => 100])->assertStatus(303);
        $this->assertSame(['50.00', '50.00'], $batch->orders()->pluck('ems_tax_amount')->all());
    }

    public function test_customer_portal_displays_only_own_allocated_bill_and_customer_cannot_change_tax(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);
        $batch = Batch::factory()->create(['ems_tax_amount' => '100.01']);
        $payload = $this->payload($batch);
        $this->post(route('admin.member-orders.store'), $payload)->assertStatus(303);
        $other = $this->payload($batch);
        $this->post(route('admin.member-orders.store'), $other)->assertStatus(303);
        $member = Member::findOrFail($payload['member_id']);
        $this->actingAs($member->user)->get(route('billing.ems'))->assertOk()->assertSee('50,01')->assertDontSee('50,00');
        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => 1])->assertForbidden();
    }

    public function test_small_tax_and_zero_tax_never_create_negative_or_phantom_bills(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create(['ems_tax_amount' => '0.01']);
        foreach (range(1, 3) as $index) {
            $this->post(route('admin.member-orders.store'), $this->payload($batch))->assertStatus(303);
        }
        $orders = $batch->orders()->orderBy('member_id')->get();
        $this->assertSame(['0.01', '0.00', '0.00'], $orders->pluck('ems_tax_amount')->all());
        $this->assertSame(['unpaid', 'not_billed', 'not_billed'], $orders->pluck('ems_tax_status')->all());
        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => 0])->assertSessionHasNoErrors();
        $this->assertSame(['0.00', '0.00', '0.00'], $batch->orders()->pluck('ems_tax_amount')->all());
        $this->assertSame(['not_billed'], $batch->orders()->pluck('ems_tax_status')->unique()->values()->all());
    }

    public function test_payment_endpoint_rejects_other_batch_order_and_archived_batch(): void
    {
        $this->actingAs(User::factory()->create());
        $batch = Batch::factory()->create(['ems_tax_amount' => 100]);
        $other = MemberOrder::factory()->create();
        $this->put(route('admin.batches.ems-payment', [$batch, $other]), ['ems_tax_status' => 'paid'])->assertNotFound();
        $batch->update(['is_archived' => true]);
        $this->put(route('admin.batches.ems-tax', $batch), ['ems_tax_amount' => 200])->assertForbidden();
    }
}
