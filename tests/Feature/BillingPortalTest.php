<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_payment_configuration_is_available_on_customer_bill(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
        $method = PaymentMethod::create([
            'type' => 'qris', 'is_active' => true,
            'name' => 'QRIS Ocean Paws', 'instructions' => 'Gunakan kode pesanan saat konfirmasi.',
        ]);
        $batch = Batch::factory()->create(['payment_method_id' => $method->id]);
        $status = OrderStatus::factory()->create(['scope' => 'payment', 'code' => 'menunggu-dp']);
        MemberOrder::factory()->create(['member_id' => $member->id, 'batch_id' => $batch->id, 'payment_status_id' => $status->id, 'total_amount' => 300000]);
        $this->actingAs($user)->get(route('billing.orders'))->assertOk()
            ->assertSee('QRIS Ocean Paws')->assertSee('Gunakan kode pesanan saat konfirmasi.');
    }

    public function test_order_billing_has_only_unpaid_and_payment_history_tabs(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
        $batch = Batch::factory()->create(['batch_name' => 'Batch Billing Caca']);
        $unpaidStatus = OrderStatus::factory()->create([
            'name' => 'Menunggu Pelunasan',
            'code' => 'menunggu-pelunasan',
            'scope' => 'payment',
        ]);
        $paidStatus = OrderStatus::factory()->create([
            'name' => 'Lunas',
            'code' => 'lunas',
            'scope' => 'payment',
        ]);

        MemberOrder::factory()->create([
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'order_code' => 'UNPAID-CACA-01',
            'payment_status_id' => $unpaidStatus->id,
            'total_amount' => 300000,
            'payment_amount' => 100000,
        ]);
        $paidOrder = MemberOrder::factory()->create([
            'member_id' => $member->id,
            'order_code' => 'PAID-CACA-01',
            'batch_id' => Batch::factory()->create()->id,
            'payment_status_id' => $paidStatus->id,
            'payment_amount' => 250000,
            'payment_submitted_at' => now(),
            'total_amount' => 250000,
        ]);

        $paidOrder->payments()->create(['type' => 'full_payment', 'amount' => 250000, 'status' => 'verified', 'paid_at' => now()]);

        $this->actingAs($user)
            ->get(route('billing.orders'))
            ->assertOk()
            ->assertSee('Tagihan Pesanan')
            ->assertSee('Belum dibayar')
            ->assertSee('Riwayat pembayaran')
            ->assertSee('UNPAID-CACA-01')
            ->assertDontSee('>PAID-CACA-01</small>', false)
            ->assertDontSee('Aktif')
            ->assertDontSee('Dikirim')
            ->assertDontSee('Refund');

        $this->actingAs($user)
            ->get(route('billing.orders', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('PAID-CACA-01')
            ->assertDontSee('>UNPAID-CACA-01</small>', false);
    }
}
