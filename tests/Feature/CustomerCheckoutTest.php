<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function setupCatalog(): array
    {
        PaymentSetting::findOrFail(1)->update(['dp_percentage' => 40]);
        OrderStatus::factory()->create(['scope' => 'payment', 'code' => 'menunggu-dp']);
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);

        $member = Member::factory()->create(['is_active' => true]);
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
            'member_id' => $member->id,
            'password_set_at' => now(),
            'registration_pending' => false,
        ]);
        $batch = Batch::factory()->create([
            'is_catalog_visible' => true,
            'ordering_deadline' => now()->addDay(),
        ]);
        $firstProduct = Product::factory()->create(['name' => 'Album', 'variant' => 'Blue', 'default_price' => 900000]);
        $secondProduct = Product::factory()->create(['name' => 'Keyring', 'variant' => 'Pink', 'default_price' => 700000]);
        $batch->products()->attach($firstProduct, ['full_price' => 100000, 'dp_price' => 99999, 'is_available' => true]);
        $batch->products()->attach($secondProduct, ['full_price' => 50000, 'dp_price' => 1, 'is_available' => true]);

        return [$customer, $member, $batch, $firstProduct, $secondProduct];
    }

    public function test_catalog_checkout_calculates_total_and_snapshots_global_dp_percentage(): void
    {
        [$customer, $member, $batch, $firstProduct, $secondProduct] = $this->setupCatalog();

        $this->actingAs($customer)->get(route('catalog.show', $batch))->assertOk()
            ->assertSee('Konfirmasi pesanan')->assertSee('name="payment_type" value="dp"', false)
            ->assertSee('name="payment_type" value="full"', false);

        $response = $this->post(route('catalog.orders.store', $batch), [
            'payment_type' => 'dp',
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 2, 'unit_price' => 1],
                ['product_id' => $secondProduct->id, 'quantity' => 1, 'unit_price' => 1],
            ],
        ]);

        $order = MemberOrder::where('member_id', $member->id)->where('batch_id', $batch->id)->sole();
        $this->assertSame('catalog', $order->order_source);
        $this->assertSame('dp', $order->payment_type);
        $this->assertSame('40.00', $order->dp_percentage);
        $this->assertSame('250000.00', $order->total_amount);
        $this->assertEquals(100000, $order->next_payment['amount']);
        $this->assertSame(['200000.00', '50000.00'], $order->items()->orderBy('id')->pluck('subtotal')->all());
        $response->assertRedirect(route('billing.payments.show', [$order, 'order']));

        PaymentSetting::findOrFail(1)->update(['dp_percentage' => 60]);
        $this->assertSame('40.00', $order->fresh()->dp_percentage);
        $this->assertEquals(100000, $order->fresh()->next_payment['amount']);
    }

    public function test_catalog_checkout_full_payment_uses_server_prices_and_stops_duplicate_orders(): void
    {
        [$customer, $member, $batch, $product] = $this->setupCatalog();
        $payload = [
            'payment_type' => 'full',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 0]],
        ];

        $this->actingAs($customer)->post(route('catalog.orders.store', $batch), $payload)->assertRedirect();
        $order = MemberOrder::where('member_id', $member->id)->where('batch_id', $batch->id)->sole();
        $this->assertSame('100000.00', $order->total_amount);
        $this->assertNull($order->dp_percentage);
        $this->assertEquals(100000, $order->next_payment['amount']);

        $this->post(route('catalog.orders.store', $batch), $payload)->assertRedirect(route('billing.payments.show', [$order, 'order']));
        $this->assertSame(1, MemberOrder::where('member_id', $member->id)->where('batch_id', $batch->id)->count());
    }

    public function test_only_approved_customer_can_checkout_and_closed_or_unavailable_products_are_rejected(): void
    {
        [$customer, , $batch, $product] = $this->setupCatalog();
        $payload = ['payment_type' => 'dp', 'items' => [['product_id' => $product->id, 'quantity' => 1]]];

        $this->post(route('catalog.orders.store', $batch), $payload)->assertRedirect(route('login'));
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->post(route('catalog.orders.store', $batch), $payload)->assertForbidden();

        $this->actingAs($customer);
        $batch->update(['ordering_deadline' => now()->subMinute()]);
        $this->from(route('catalog.show', $batch))->post(route('catalog.orders.store', $batch), $payload)->assertSessionHasErrors('batch');

        $batch->update(['ordering_deadline' => now()->addDay()]);
        $batch->products()->updateExistingPivot($product->id, ['is_available' => false]);
        $this->from(route('catalog.show', $batch))->post(route('catalog.orders.store', $batch), $payload)->assertSessionHasErrors('items');
        $this->assertSame(0, MemberOrder::count());
    }

    public function test_checkout_keeps_selected_quantities_and_payment_type_after_validation_error(): void
    {
        [$customer, , $batch, $firstProduct, $secondProduct] = $this->setupCatalog();
        $detailUrl = route('catalog.show', $batch);

        $this->actingAs($customer)->from($detailUrl)->post(route('catalog.orders.store', $batch), [
            'payment_type' => 'invalid',
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 0],
                ['product_id' => $secondProduct->id, 'quantity' => 2],
            ],
        ])->assertRedirect($detailUrl)->assertSessionHasErrors('payment_type');

        $this->get($detailUrl)->assertOk()
            ->assertSee('name="items[0][quantity]" value="0"', false)
            ->assertSee('name="items[1][quantity]" value="2"', false);
    }
}
