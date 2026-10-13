<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPortalTest extends TestCase
{
    use RefreshDatabase;

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
        MemberOrder::factory()->create([
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'order_code' => 'PAID-CACA-01',
            'payment_status_id' => $paidStatus->id,
            'payment_amount' => 250000,
            'payment_submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('billing.orders'))
            ->assertOk()
            ->assertSee('Tagihan Pesanan')
            ->assertSee('Belum dibayar')
            ->assertSee('Riwayat pembayaran')
            ->assertSee('UNPAID-CACA-01')
            ->assertDontSee('PAID-CACA-01')
            ->assertDontSee('Aktif')
            ->assertDontSee('Dikirim')
            ->assertDontSee('Refund');

        $this->actingAs($user)
            ->get(route('billing.orders', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('PAID-CACA-01')
            ->assertDontSee('UNPAID-CACA-01');
    }
}
