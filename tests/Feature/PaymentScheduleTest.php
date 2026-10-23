<?php

namespace Tests\Feature;

use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        Storage::fake('local');
        foreach (['menunggu-dp', 'menunggu-pelunasan', 'lunas'] as $code) {
            OrderStatus::factory()->create(['scope' => 'payment', 'code' => $code]);
        }
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);
        $order = MemberOrder::factory()->create(['total_amount' => 100000, 'payment_type' => 'dp']);
        $customer = User::factory()->create(['role' => 'customer', 'member_id' => $order->member_id]);

        return [$order, $customer, User::factory()->create(['role' => 'admin'])];
    }

    private function submit(MemberOrder $order, User $customer, float $amount = 50000)
    {
        return $this->actingAs($customer)->from(route('billing.payments.show', [$order, 'order']))->post(route('billing.payments.store', $order), [
            'bill_type' => 'order', 'amount' => $amount,
            'submission_key' => (string) Str::uuid(), 'payment_form' => $order->id.'-order',
            'proof' => UploadedFile::fake()->image('receipt.png'),
        ]);
    }

    public function test_dp_is_system_calculated_then_remaining_balance_is_due_only_after_verification(): void
    {
        [$order, $customer, $admin] = $this->scenario();
        $this->actingAs($customer)->get(route('billing.index'))->assertOk()
            ->assertViewHas('outstandingTotal', 50000.0)->assertSee('DP yang harus dibayar');
        $this->get(route('billing.payments.show', [$order, 'order']))->assertOk()->assertSee('Harga keseluruhan')
            ->assertSee('type="hidden" name="amount" value="50000"', false)
            ->assertDontSee('type="number" name="amount"', false);
        $this->submit($order, $customer, 30000)->assertSessionHasErrors('amount');
        $this->submit($order, $customer, 100000)->assertSessionHasErrors('amount');
        $this->submit($order, $customer)->assertSessionHasNoErrors();
        $payment = $order->payments()->firstOrFail();
        $this->assertSame('dp', $payment->type);
        $this->assertSame('50.00', $order->fresh()->dp_percentage);
        $this->get(route('billing.index'))->assertViewHas('outstandingTotal', 50000.0);
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->actingAs($customer)->get(route('billing.index'))->assertViewHas('outstandingTotal', 50000.0)->assertSee('Pelunasan Pesanan');
        $this->submit($order, $customer)->assertSessionHasNoErrors();
        $settlement = $order->payments()->latest('id')->first();
        $this->assertSame('settlement', $settlement->type);
        $this->actingAs($admin)->post(route('admin.transactions.review', $settlement), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('lunas', $order->fresh()->paymentStatus->code);
    }

    public function test_full_choice_requires_full_amount_despite_dp_setting(): void
    {
        [$order, $customer] = $this->scenario();
        $order->update(['payment_type' => 'full']);
        $this->actingAs($customer)->get(route('billing.index'))->assertViewHas('outstandingTotal', 100000.0);
        $this->submit($order, $customer)->assertSessionHasErrors('amount');
        $this->submit($order, $customer, 100000)->assertSessionHasNoErrors();
        $this->assertSame('full_payment', $order->payments()->first()->type);
        $this->assertNull($order->fresh()->dp_percentage);
    }

    public function test_admin_settings_are_validated_authorized_and_reject_stale_quotes(): void
    {
        [$order, $customer, $admin] = $this->scenario();
        $this->actingAs($customer)->put(route('admin.transactions.settings'), ['dp_percentage' => 25])->assertForbidden();
        $this->actingAs($admin)->from(route('admin.transactions.index'))->put(route('admin.transactions.settings'), ['dp_percentage' => 0])->assertSessionHasErrors('dp_percentage');
        $this->put(route('admin.transactions.settings'), ['dp_percentage' => 100])->assertSessionHasErrors('dp_percentage');
        $this->put(route('admin.transactions.settings'), ['dp_percentage' => 33.333])->assertSessionHasErrors('dp_percentage');
        $this->put(route('admin.transactions.settings'), ['dp_percentage' => 25])->assertSessionHasNoErrors();
        $this->assertSame('25.00', PaymentSetting::find(1)->dp_percentage);
        $this->submit($order, $customer, 50000)->assertSessionHasErrors('amount');
        $this->assertNull($order->fresh()->dp_percentage);
        $this->assertSame(0, $order->payments()->count());
        $this->assertCount(0, Storage::disk('local')->allFiles('payment-proofs'));
        $this->submit($order, $customer, 25000)->assertSessionHasNoErrors();
        $this->assertSame('25.00', $order->fresh()->dp_percentage);
    }

    public function test_setting_changes_do_not_reprice_pending_or_rejected_dp_proof(): void
    {
        [$order, $customer, $admin] = $this->scenario();
        $this->submit($order, $customer)->assertSessionHasNoErrors();
        $payment = $order->payments()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.transactions.settings'), ['dp_percentage' => 70])->assertSessionHasNoErrors();
        $this->assertSame(50000.0, $order->fresh()->next_payment['amount']);
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'rejected', 'review_notes' => 'Bukti kurang jelas'])->assertSessionHasNoErrors();
        $this->submit($order, $customer, 70000)->assertSessionHasErrors('amount');
        $this->submit($order, $customer)->assertSessionHasNoErrors();
        $replacement = $order->payments()->latest('id')->first();
        $this->actingAs($admin)->post(route('admin.transactions.review', $replacement), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame(50000.0, $order->fresh()->next_payment['amount']);
        $this->assertSame('settlement', $order->fresh()->next_payment['type']);
    }

    public function test_legacy_verified_dp_is_not_recharged_and_percentage_rounds_to_two_decimals(): void
    {
        [$order, $customer, $admin] = $this->scenario();
        $order->payments()->create(['type' => 'dp', 'status' => 'verified', 'amount' => 40000]);
        $this->assertSame(60000.0, $order->fresh()->next_payment['amount']);
        $this->assertSame('settlement', $order->fresh()->next_payment['type']);
        $this->actingAs($admin)->put(route('admin.transactions.settings'), ['dp_percentage' => 33.33])->assertSessionHasNoErrors();
        $other = MemberOrder::factory()->create(['payment_type' => 'dp', 'total_amount' => 100001]);
        $this->assertSame(33330.33, $other->next_payment['amount']);
    }
}
