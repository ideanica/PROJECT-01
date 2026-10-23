<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingFinancialCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_bills_are_financial_cards_without_product_content(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['ems_tax_amount' => 25000, 'ems_tax_status' => 'unpaid']);
        $order->batch->update(['catalog_image_path' => 'private-product-thumbnail.png']);
        OrderItem::factory()->create(['member_order_id' => $order->id, 'item_name' => 'Product not for billing', 'variant' => 'Variant not for billing']);
        $response = $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('Tagihan Aktif')->assertSee('Riwayat Pembayaran')->assertSee('Bayar Sekarang')
            ->assertDontSee('private-product-thumbnail.png')->assertDontSee('Product not for billing')->assertDontSee('Variant not for billing')
            ->assertDontSee('<article class="portal-v2-order-card', false)->assertDontSee('class="portal-v2-thumb"', false)
            ->assertViewHas('outstandingCount', 2)->assertViewHas('outstandingTotal', 125000.0);
        $this->assertSame(2, substr_count($response->getContent(), 'Bayar Sekarang'));
    }

    public function test_history_lists_actual_payments_of_one_order_separately_and_newest_first(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['total_amount' => 300000]);
        $settlement = $this->payment($order, ['type' => 'settlement', 'amount' => 200000, 'paid_at' => '2026-10-12 10:00:00']);
        $dp = $this->payment($order, ['type' => 'dp', 'amount' => 100000, 'paid_at' => '2026-09-26 10:00:00']);
        $response = $this->actingAs($user)->get(route('billing.index', ['tab' => 'history', 'sort' => 'oldest']))->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('id')->all() === [$settlement->id, $dp->id])
            ->assertViewHas('outstandingCount', 0)->assertSeeInOrder(['Pelunasan Pesanan', 'DP Pesanan'])
            ->assertSee('Rp 200.000')->assertSee('Rp 100.000')->assertSee('Terverifikasi')
            ->assertDontSee('Rp 300.000')->assertDontSee('Bayar Sekarang')->assertDontSee('<article class="portal-v2-order-card', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-payment-id='));
        $response->assertSeeInOrder([$settlement->paid_at->translatedFormat('F Y'), $dp->paid_at->translatedFormat('F Y')]);
    }

    public function test_history_excludes_pending_rejected_and_nonpositive_transactions_but_shows_verified_refunds(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $verified = $this->payment($order, ['amount' => 10000]);
        $refund = null;
        foreach ([
            ['status' => 'pending', 'amount' => 11000],
            ['status' => 'rejected', 'amount' => 12000],
            ['type' => 'refund', 'amount' => 13000],
            ['type' => 'unknown-adjustment', 'amount' => 14000],
            ['amount' => 0], ['amount' => -15000],
        ] as $attributes) {
            $payment = $this->payment($order, $attributes);
            if (($attributes['type'] ?? null) === 'refund') $refund = $payment;
        }
        $this->actingAs($user)->get(route('billing.index', ['tab' => 'history']))->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('id')->all() === [$refund->id, $verified->id])
            ->assertDontSee('Rp 11.000')->assertDontSee('Rp 12.000')->assertSee('−Rp 13.000')
            ->assertDontSee('Rp 14.000')->assertDontSee('Rp -15.000');
    }

    public function test_history_uses_creation_date_when_paid_at_is_missing_and_breaks_date_ties_consistently(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $fallback = $this->payment($order, ['paid_at' => null]);
        $fallback->forceFill(['created_at' => '2026-09-01 12:00:00'])->save();
        $earlier = $this->payment($order, ['paid_at' => '2026-10-01 12:00:00']);
        $later = $this->payment($order, ['paid_at' => '2026-10-01 12:00:00']);
        $this->actingAs($user)->get(route('billing.index', ['tab' => 'history']))->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('id')->all() === [$later->id, $earlier->id, $fallback->id])
            ->assertSee($fallback->created_at->translatedFormat('d F Y'));
    }

    public function test_refunded_order_history_shows_incoming_and_outgoing_transactions_separately(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['override_status_id' => OrderStatus::where('code', 'refunded')->sole()->id]);
        $dp = $this->payment($order, ['amount' => 30000]);
        $refund = $this->payment($order, ['type' => 'refund', 'amount' => 30000]);
        $this->actingAs($user)->get(route('billing.index', ['tab' => 'history']))->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('id')->all() === [$refund->id, $dp->id])
            ->assertViewHas('outstandingCount', 0)->assertSee('Pesanan direfund')->assertSee('−Rp 30.000')->assertDontSee('Bayar Sekarang');
    }

    public function test_legacy_payment_without_any_date_is_shown_without_inventing_a_date(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $undated = $this->payment($order, ['paid_at' => null]);
        $undated->forceFill(['created_at' => null])->save();
        $dated = $this->payment($order, ['paid_at' => '2026-10-12 12:00:00']);
        $this->actingAs($user)->get(route('billing.index', ['tab' => 'history']))->assertOk()
            ->assertSee('Tanggal belum tersedia')
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('id')->all() === [$dated->id, $undated->id]);
    }

    public function test_history_cannot_show_another_customers_transactions(): void
    {
        $user = $this->customer();
        $own = $this->order($user, ['order_code' => 'OWN-TRANSACTION']);
        $ownPayment = $this->payment($own, ['amount' => 12345]);
        $other = $this->order($this->customer(), ['order_code' => 'SECRET-TRANSACTION']);
        $this->payment($other, ['amount' => 98765]);
        $this->actingAs($user)->get(route('billing.index', ['tab' => 'history', 'member_id' => $other->member_id]))->assertOk()
            ->assertSee('OWN-TRANSACTION')->assertDontSee('SECRET-TRANSACTION')->assertDontSee('Rp 98.765')
            ->assertViewHas('payments', fn ($payments) => $payments->pluck('id')->all() === [$ownPayment->id]);
        $this->get(route('billing.index', ['tab' => 'history', 'q' => 'SECRET-TRANSACTION']))
            ->assertOk()->assertViewHas('payments', fn ($payments) => $payments->isEmpty());
    }

    public function test_history_links_preserve_signed_return_destination_and_current_tab(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['order_code' => 'HISTORY-NAV']);
        $this->payment($order);
        $path = '/billing?tab=history&q=HISTORY-NAV';
        $response = $this->actingAs($user)->get($path)->assertOk();
        $payment = $response->viewData('payments')->first();
        $url = $payment->order->billing_tracking_url;
        $this->assertStringContainsString('return_to='.urlencode($path), $url);
        $this->get($url)->assertOk()->assertViewHas('backUrl', $path);
        $this->get($url.'&return_to=//example.com')->assertForbidden();
        $response->assertSee('name="tab" value="history"', false);
    }

    public function test_empty_active_tab_still_links_to_history_and_history_has_its_own_empty_state(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('Tidak ada tagihan aktif.')->assertSee(route('billing.index', ['tab' => 'history']), false)
            ->assertDontSee('Bayar Sekarang');
        $this->get(route('billing.index', ['tab' => 'history']))->assertOk()->assertSee('Belum ada riwayat pembayaran.');
        $order = $this->order($user, ['total_amount' => 0, 'ems_tax_amount' => 50000, 'ems_tax_status' => 'paid']);
        $this->get(route('billing.index', ['tab' => 'history']))->assertViewHas('payments', fn ($payments) => $payments->isEmpty());
        $this->payment($order, ['amount' => 10000]);
        $this->get(route('billing.index', ['tab' => 'history']))->assertSee('Rp 10.000')->assertDontSee('Rp 50.000');
    }

    public function test_search_keeps_summary_and_profile_count_based_on_all_outstanding_obligations(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['total_amount' => 110000, 'ems_tax_amount' => 25000, 'ems_tax_status' => 'unpaid']);
        $this->payment($order, ['amount' => 10000]);
        foreach ([[], ['tab' => 'history'], ['q' => 'not found'], ['tab' => 'history', 'q' => 'not found']] as $query) {
            $this->actingAs($user)->get(route('billing.index', $query))->assertOk()
                ->assertViewHas('outstandingCount', 2)->assertViewHas('outstandingTotal', 125000.0);
        }
        $this->get(route('profile.show'))->assertOk()->assertViewHas('stats', fn ($stats) => $stats['unpaid'] === 2);
    }

    public function test_payment_action_reuses_existing_configured_method_and_detail_flow(): void
    {
        $user = $this->customer();
        $status = OrderStatus::factory()->create(['scope' => 'payment', 'code' => 'menunggu-dp']);
        $order = $this->order($user, ['payment_status_id' => $status->id]);
        $method = PaymentMethod::create(['name' => 'Financial QRIS', 'type' => 'qris', 'is_active' => true, 'image_path' => 'financial-qris.png']);
        $order->batch->update(['payment_method_id' => $method->id]);
        $response = $this->actingAs($user)->get(route('billing.index'))->assertOk()
            ->assertSee('DP Pesanan')->assertSee('Bayar Sekarang')->assertDontSee('financial-qris.png');
        $paymentUrl = route('billing.payments.show', [$order, 'order']);
        $response->assertSee($paymentUrl);
        $this->get($paymentUrl)->assertOk()->assertSee('Financial QRIS')->assertSee('financial-qris.png');
        $url = $response->viewData('obligations')->first()['order']->billing_tracking_url;
        $response->assertSee($url.'#payment-summary');
        $this->get($url)->assertOk()->assertSee($paymentUrl)->assertDontSee('financial-qris.png');
    }

    public function test_guests_and_admins_cannot_view_customer_transaction_data(): void
    {
        $customer = $this->customer();
        $this->payment($this->order($customer, ['order_code' => 'PRIVATE-BILLING']));
        $this->get(route('billing.index', ['tab' => 'history']))->assertOk()->assertSee('Login untuk melihat')->assertDontSee('PRIVATE-BILLING');
        $admin = User::factory()->create(['role' => 'admin', 'member_id' => $customer->member_id]);
        $this->actingAs($admin)->get(route('billing.index', ['tab' => 'history']))->assertOk()
            ->assertSee('AKUN ADMIN')->assertDontSee('PRIVATE-BILLING')->assertViewHas('payments', fn ($payments) => $payments->isEmpty());
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'member_id' => Member::factory()->create()->id]);
    }

    private function order(User $user, array $attributes = []): MemberOrder
    {
        return MemberOrder::factory()->create(['member_id' => $user->member_id, 'total_amount' => 100000, ...$attributes]);
    }

    private function payment(MemberOrder $order, array $attributes = [])
    {
        return $order->payments()->create(['type' => 'dp', 'amount' => 10000, 'status' => 'verified', 'paid_at' => now(), ...$attributes]);
    }
}
