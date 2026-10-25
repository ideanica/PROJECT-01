<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\BatchEmsTaxService;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerOrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function setupCustomerOrder(array $orderAttributes = []): array
    {
        $this->seed(OrderStatusSeeder::class);

        $member = Member::factory()->create(['is_active' => true]);
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'member_id' => $member->id,
            'registration_pending' => false,
        ]);
        $batch = Batch::factory()->create(['is_catalog_visible' => true]);
        $order = MemberOrder::factory()->create(array_merge([
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'order_source' => 'catalog',
            'payment_type' => 'dp',
            'dp_percentage' => 40,
            'total_amount' => 100000,
        ], $orderAttributes));

        return [$customer, $member, $batch, $order];
    }

    public function test_customer_can_cancel_an_order_with_no_payment_history(): void
    {
        [$customer, , , $order] = $this->setupCustomerOrder();
        $detailUrl = URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), [
            'memberCode' => $order->member->member_code,
            'memberOrder' => $order,
        ]);

        $this->actingAs($customer)->get($detailUrl)->assertOk()->assertSee('Batalkan pesanan');
        $this->from($detailUrl)->post(route('orders.cancel', $order))
            ->assertRedirect($detailUrl)
            ->assertSessionHas('status', 'Pesanan berhasil dibatalkan.');

        $order->refresh();
        $this->assertTrue($order->is_cancelled);
        $this->assertFalse($order->has_outstanding_order_payment);
        $this->assertSame(
            'Pesanan dibatalkan oleh customer sebelum pembayaran.',
            $order->statusHistories()->latest('id')->value('note'),
        );
    }

    public function test_customer_cannot_cancel_an_order_with_any_payment_record_or_legacy_proof(): void
    {
        [$customer, , , $order] = $this->setupCustomerOrder();
        $order->payments()->create(['type' => 'dp', 'status' => 'pending', 'amount' => 40000]);

        $this->actingAs($customer)->post(route('orders.cancel', $order))
            ->assertSessionHasErrors('order');
        $this->assertFalse($order->fresh()->is_cancelled);

        $order->payments()->delete();
        $order->update(['payment_proof_path' => 'payment-proofs/legacy.jpg']);
        $this->post(route('orders.cancel', $order))->assertSessionHasErrors('order');
        $this->assertFalse($order->fresh()->is_cancelled);
    }

    public function test_only_the_order_owner_can_cancel_it(): void
    {
        [, , , $order] = $this->setupCustomerOrder();
        $otherMember = Member::factory()->create(['is_active' => true]);
        $otherCustomer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'member_id' => $otherMember->id,
            'registration_pending' => false,
        ]);

        $this->actingAs($otherCustomer)->post(route('orders.cancel', $order))->assertForbidden();
        $this->assertFalse($order->fresh()->is_cancelled);
    }

    public function test_cancelling_an_unpaid_order_reallocates_unprocessed_batch_ems(): void
    {
        [$customer, , $batch, $order] = $this->setupCustomerOrder();
        $batch->update(['ems_tax_amount' => 100000]);
        $otherMember = Member::factory()->create(['is_active' => true]);
        $otherOrder = MemberOrder::factory()->create([
            'member_id' => $otherMember->id,
            'batch_id' => $batch->id,
            'total_amount' => 100000,
        ]);
        app(BatchEmsTaxService::class)->sync($batch);
        $this->assertSame('50000.00', $order->fresh()->ems_tax_amount);
        $this->assertSame('50000.00', $otherOrder->fresh()->ems_tax_amount);

        $this->actingAs($customer)->post(route('orders.cancel', $order))->assertSessionHasNoErrors();

        $this->assertSame('100000.00', $otherOrder->fresh()->ems_tax_amount);
        $this->assertFalse($order->fresh()->has_outstanding_ems_tax);
    }

    public function test_catalog_order_payment_page_returns_to_its_batch_detail(): void
    {
        [$customer, , $batch, $order] = $this->setupCustomerOrder();

        $this->actingAs($customer)->get(route('billing.payments.show', [$order, 'order']))
            ->assertOk()
            ->assertSee('Kembali ke detail batch')
            ->assertSee('href="'.route('catalog.show', $batch).'"', false);
    }
}
