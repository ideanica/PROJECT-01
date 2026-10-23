<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\OrderStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OutstandingBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_combines_order_balance_and_published_ems_as_separate_obligations(): void
    {
        $user = $this->customer();
        $order = $this->order($user, [
            'total_amount' => 500000, 'ems_tax_amount' => 75000.01,
            'ems_tax_status' => 'unpaid', 'ems_tax_due_date' => '2026-10-30',
        ]);
        $order->payments()->create(['type' => 'dp', 'amount' => 200000, 'status' => 'verified']);
        // Submitted or rejected money is not a settled payment.
        $order->payments()->create(['type' => 'settlement', 'amount' => 300000, 'status' => 'pending']);
        $order->payments()->create(['type' => 'settlement', 'amount' => 300000, 'status' => 'rejected']);
        $response = $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('Rp 300.000')->assertSee('Rp 75.000,01')->assertSee($order->ems_tax_due_date->translatedFormat('d F Y'))
            ->assertSee('Riwayat Pembayaran')->assertDontSee('tab=paid')->assertDontSee('tab=refund')
            ->assertDontSee('Sudah dibayar')->assertDontSee('Total pesanan');

        $response->assertViewHas('outstandingCount', 2)->assertViewHas('outstandingTotal', 375000.01);
        $response->assertViewHas('obligations', fn ($bills) => $bills->pluck('type')->all() === ['order', 'ems']
            && $bills->every(fn ($bill) => $bill['order']->id === $order->id));
        $this->assertSame(2, substr_count($response->getContent(), 'data-billing-type='));
    }

    public function test_paid_refunded_zero_and_unpublished_bills_are_excluded(): void
    {
        $user = $this->customer();
        $paid = $this->order($user, ['order_code' => 'SETTLED', 'total_amount' => 100000]);
        $paid->payments()->create(['type' => 'full_payment', 'amount' => 100000, 'status' => 'verified']);
        $refund = OrderStatus::factory()->create(['code' => 'refund', 'scope' => 'payment']);
        $refunded = OrderStatus::where('code', 'refunded')->sole();
        foreach (['payment_status_id' => $refund->id, 'override_status_id' => $refunded->id] as $field => $statusId) {
            $this->order($user, [$field => $statusId, 'ems_tax_amount' => 75000, 'ems_tax_status' => 'unpaid']);
        }
        foreach ([
            ['total_amount' => 0, 'ems_tax_amount' => 75000, 'ems_tax_status' => 'not_billed'],
            ['total_amount' => null, 'ems_tax_amount' => 75000, 'ems_tax_status' => 'paid'],
            ['total_amount' => 0, 'ems_tax_amount' => 0, 'ems_tax_status' => 'unpaid'],
            ['total_amount' => 0, 'ems_tax_amount' => null, 'ems_tax_status' => 'unpaid'],
        ] as $attributes) {
            $this->order($user, $attributes);
        }
        $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertViewHas('outstandingCount', 0)->assertViewHas('outstandingTotal', 0)
            ->assertSee('Tidak ada tagihan aktif.')->assertDontSee('data-billing-type=', false);
        $this->get(route('orders.history'))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->count() === 7);
    }

    public function test_ledger_controls_outstanding_even_when_payment_status_is_stale(): void
    {
        $user = $this->customer();
        $lunas = OrderStatus::factory()->create(['code' => 'lunas', 'scope' => 'payment']);
        $dp = OrderStatus::factory()->create(['code' => 'menunggu-dp', 'scope' => 'payment']);
        $this->order($user, ['order_code' => 'STALE-LUNAS', 'payment_status_id' => $lunas->id]);
        $paid = $this->order($user, ['order_code' => 'STALE-DP', 'payment_status_id' => $dp->id]);
        $paid->payments()->create(['type' => 'full_payment', 'amount' => 100000, 'status' => 'verified']);
        $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('STALE-LUNAS')->assertDontSee('STALE-DP')->assertViewHas('outstandingCount', 1);
    }

    public function test_settling_each_obligation_removes_only_that_bill_and_updates_profile_badge(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['ems_tax_amount' => 25000, 'ems_tax_status' => 'unpaid']);
        $other = $this->order($this->customer(), ['ems_tax_amount' => 99000, 'ems_tax_status' => 'unpaid']);
        $this->actingAs($user)->get(route('profile.show'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['unpaid'] === 2)
            ->assertViewHas('summary', fn ($summary) => $summary['orders'] === 1)
            ->assertSee(route('billing.index'), false);
        $order->payments()->create(['type' => 'full_payment', 'amount' => 100000, 'status' => 'verified']);
        $this->get(route('billing.index'))->assertOk()->assertViewHas('outstandingCount', 1)
            ->assertViewHas('obligations', fn ($bills) => $bills->pluck('type')->all() === ['ems'])
            ->assertDontSee($other->order_code);
        $this->get(route('profile.show'))->assertViewHas('stats', fn ($stats) => $stats['unpaid'] === 1);
        $order->update(['ems_tax_status' => 'paid']);
        $this->get(route('billing.index'))->assertViewHas('outstandingCount', 0);
        $this->get(route('profile.show'))->assertViewHas('stats', fn ($stats) => $stats['unpaid'] === 0);
        $this->get(route('orders.history'))->assertSee($order->order_code);
    }

    public function test_history_filters_follow_lifecycle_and_do_not_overlap_shipping_or_refunds(): void
    {
        $this->seed(OrderStatusSeeder::class);
        $user = $this->customer();
        $expected = [];
        foreach (['active' => 'siap-distribusi', 'shipping' => 'dikirim-ke-customer', 'history' => 'selesai', 'refund' => 'refunded', 'cancelled' => 'dibatalkan'] as $filter => $code) {
            $status = OrderStatus::where('code', $code)->sole();
            $expected[$filter] = $this->order($user, ['override_status_id' => $status->id])->id;
        }
        $this->actingAs($user)->get(route('orders.history'))->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->count() === 5)
            ->assertDontSee('filter=unpaid')->assertDontSee('>Belum bayar</span>', false)
            ->assertSee('Lihat Detail');
        foreach (['active', 'shipping', 'history', 'refund'] as $filter) {
            $this->get(route('orders.history', ['filter' => $filter]))->assertOk()
                ->assertViewHas('orders', fn ($orders) => $orders->modelKeys() === [$expected[$filter]]);
        }
    }

    public function test_legacy_billing_and_unpaid_links_redirect_without_losing_search(): void
    {
        foreach (['billing.orders', 'billing.ems', 'orders.unpaid'] as $route) {
            $this->get(route($route, ['q' => 'album', 'sort' => 'oldest']))
                ->assertRedirect(route('billing.index', ['q' => 'album', 'sort' => 'oldest']));
        }
        foreach (['billing.orders', 'billing.ems', 'billing.index'] as $route) {
            foreach (['paid', 'history'] as $tab) {
                $response = $this->get(route($route, ['tab' => $tab, 'q' => 'album']));
                if ($route === 'billing.index' && $tab === 'history') {
                    $response->assertOk()->assertViewHas('tab', 'history');
                } else {
                    $response->assertRedirect(route('billing.index', ['tab' => 'history', 'q' => 'album']));
                }
            }
            $this->get(route($route, ['tab' => 'refund']))->assertRedirect(route('orders.history', ['filter' => 'refund']));
            $this->get(route($route, ['tab' => 'unpaid']))->assertRedirect(route('billing.index'));
        }
        $this->get(route('orders.history', ['filter' => 'unpaid']))->assertRedirect(route('billing.index'));
        $this->get(route('orders.index'))->assertRedirect(route('orders.history'));
    }

    public function test_search_and_sort_use_individual_bill_amounts_and_keep_overall_summary(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['order_code' => 'MATCH-BILL', 'ems_tax_amount' => 50000, 'ems_tax_status' => 'unpaid']);
        $this->order($user, ['order_code' => 'OTHER-BILL', 'total_amount' => 75000]);
        $response = $this->actingAs($user)->get(route('billing.index', ['sort' => 'amount_desc']))->assertOk();
        $response->assertViewHas('obligations', fn ($bills) => $bills->pluck('amount')->all() === [100000.0, 75000.0, 50000.0]);
        $this->get(route('billing.index', ['q' => $order->order_code, 'sort' => 'amount_asc']))->assertOk()
            ->assertViewHas('obligations', fn ($bills) => $bills->pluck('amount')->all() === [50000.0, 100000.0])
            ->assertViewHas('outstandingCount', 3)->assertDontSee('OTHER-BILL');
        $this->get(route('billing.index', ['q' => 'missing']))->assertOk()->assertSee('Tidak ada tagihan yang cocok.');
    }

    public function test_ems_only_payment_method_and_history_remain_available_in_order_detail(): void
    {
        $user = $this->customer();
        $method = PaymentMethod::create(['name' => 'EMS QRIS', 'type' => 'qris', 'is_active' => true, 'image_path' => 'ems-qris.png']);
        $order = $this->order($user, ['ems_tax_amount' => 50000, 'ems_tax_status' => 'unpaid']);
        $order->batch->update(['payment_method_id' => $method->id]);
        $order->payments()->create(['type' => 'full_payment', 'amount' => 100000, 'status' => 'verified', 'paid_at' => now()]);
        $paymentUrl = route('billing.payments.show', [$order, 'ems']);
        $this->actingAs($user)->get(route('billing.index'))->assertOk()->assertSee($paymentUrl)->assertDontSee('ems-qris.png');
        $this->get($paymentUrl)->assertOk()->assertSee('EMS QRIS')->assertSee('ems-qris.png');
        $url = $this->detailUrl($order, '/billing?q=EMS');
        $this->get($url)->assertOk()->assertSee($paymentUrl)->assertSee('Riwayat pembayaran (1)')
            ->assertSee('Rp 100.000')->assertSee('Rp 50.000')->assertViewHas('backUrl', '/billing?q=EMS');
        $order->update(['ems_tax_status' => 'paid']);
        $this->get($url)->assertOk()->assertSee('Riwayat pembayaran (1)')->assertDontSee('Bayar Sekarang');
    }

    public function test_refunded_order_keeps_financial_history_without_payment_actions(): void
    {
        $user = $this->customer();
        $status = OrderStatus::where('code', 'refunded')->sole();
        $order = $this->order($user, ['override_status_id' => $status->id, 'ems_tax_amount' => 50000, 'ems_tax_status' => 'unpaid']);
        $order->payments()->create(['type' => 'dp', 'amount' => 25000, 'status' => 'verified']);
        $this->actingAs($user)->get($this->detailUrl($order))->assertOk()
            ->assertSee('Direfund')->assertSee('Riwayat pembayaran (1)')->assertSee('Rp 25.000')
            ->assertDontSee('Bayar Sekarang');
    }

    public function test_ownership_signatures_and_payment_proofs_remain_protected(): void
    {
        $user = $this->customer();
        $own = $this->order($user);
        $other = $this->order($this->customer(), ['order_code' => 'PRIVATE-OTHER']);
        $this->actingAs($user)->get(route('billing.index', ['member_id' => $other->member_id]))
            ->assertOk()->assertSee($own->order_code)->assertDontSee('PRIVATE-OTHER');
        $this->get(URL::temporarySignedRoute('tracking.order', now()->addMinutes(10), [
            'memberCode' => $own->member->member_code, 'memberOrder' => $other,
        ]))->assertForbidden();
        $this->get($this->detailUrl($own).'&return_to=//example.com')->assertForbidden();
        $this->get(URL::temporarySignedRoute('tracking.order', now()->subMinute(), [
            'memberCode' => $own->member->member_code, 'memberOrder' => $own,
        ]))->assertForbidden();
        $this->get(route('profile.orders.payment-proof', $other))->assertForbidden();
    }

    public function test_payment_stage_labels_use_configured_status_without_guessing_from_amounts(): void
    {
        $user = $this->customer();
        foreach (['menunggu-dp' => 'DP', 'menunggu-pelunasan' => 'Pelunasan'] as $code => $label) {
            $status = OrderStatus::factory()->create(['code' => $code, 'scope' => 'payment']);
            $order = $this->order($user, ['payment_status_id' => $status->id]);
            $this->assertSame($label, $order->outstanding_obligations[0]['label']);
            $this->assertSame(100000.0, $order->outstanding_obligations[0]['amount']);
        }
        $order = $this->order($user);
        $order->payments()->create(['type' => 'dp', 'amount' => 25000, 'status' => 'verified']);
        $this->assertSame('Pembayaran pesanan', $order->outstanding_obligations[0]['label']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'member_id' => Member::factory()->create()->id]);
    }

    private function order(User $user, array $attributes = []): MemberOrder
    {
        return MemberOrder::factory()->create(['member_id' => $user->member_id, 'total_amount' => 100000, ...$attributes]);
    }

    private function detailUrl(MemberOrder $order, string $returnTo = '/order-history'): string
    {
        return URL::temporarySignedRoute('tracking.order', now()->addMinutes(10), [
            'memberCode' => $order->member->member_code, 'memberOrder' => $order, 'return_to' => $returnTo,
        ]);
    }
}
