<?php

namespace Tests\Feature;

use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function setupOrder(): array
    {
        Storage::fake('local');
        \App\Models\PaymentSetting::findOrFail(1)->update(['dp_percentage' => 40]);
        foreach (['menunggu-dp', 'menunggu-pelunasan', 'lunas'] as $code) {
            OrderStatus::factory()->create(['scope' => 'payment', 'code' => $code]);
        }
        $order = MemberOrder::factory()->create(['payment_type' => 'dp', 'total_amount' => 100000, 'ems_tax_amount' => 20000, 'ems_tax_status' => 'unpaid']);
        $customer = User::factory()->create(['role' => 'customer', 'member_id' => $order->member_id]);
        $admin = User::factory()->create(['role' => 'admin']);

        return [$order, $customer, $admin];
    }

    private function submit(MemberOrder $order, User $user, array $overrides = [])
    {
        return $this->actingAs($user)->from(route('billing.payments.show', [$order, $overrides['bill_type'] ?? 'order']))->post(route('billing.payments.store', $order), array_merge([
            'payment_form' => $order->id.'-order', 'bill_type' => 'order',
            'submission_key' => (string) Str::uuid(), 'amount' => 40000,
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ], $overrides));
    }

    public function test_pending_dp_only_counts_after_admin_verification_then_settlement_makes_order_paid(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $this->submit($order, $customer)->assertSessionHasNoErrors()->assertRedirect(route('billing.payments.show', [$order, 'order']));
        $payment = $order->payments()->firstOrFail();
        $this->assertSame('dp', $payment->type);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(0.0, $order->fresh()->paid_amount);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->actingAs($admin)->get(route('admin.transactions.index'))->assertOk()->assertSee($order->order_code);
        $this->get(route('admin.transactions.show', $payment))->assertOk()->assertSee('Verifikasi pembayaran');
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame($admin->id, $payment->fresh()->reviewed_by);
        $this->assertNotNull($payment->fresh()->reviewed_at);
        $this->assertSame(60000.0, $order->fresh()->remaining_amount);
        $this->assertSame('menunggu-pelunasan', $order->fresh()->paymentStatus->code);
        $this->submit($order, $customer, ['amount' => 60000])->assertSessionHasNoErrors();
        $settlement = $order->payments()->latest('id')->first();
        $this->assertSame('settlement', $settlement->type);
        $this->actingAs($admin)->post(route('admin.transactions.review', $settlement), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('lunas', $order->fresh()->paymentStatus->code);
        $this->assertSame(0.0, $order->fresh()->remaining_amount);
    }

    public function test_customer_payment_proof_must_be_an_image(): void
    {
        [$order, $customer] = $this->setupOrder();

        $this->submit($order, $customer, [
            'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ])->assertSessionHasErrors('proof');

        $this->assertSame(0, $order->payments()->count());
    }

    public function test_admin_manual_payment_must_match_system_schedule_and_cannot_choose_status_or_type(): void
    {
        [$order, , $admin] = $this->setupOrder();
        $url = route('admin.member-orders.payments.store', $order);

        $this->actingAs($admin)->post($url, [
            'amount' => 100000,
            'type' => 'other',
            'status' => 'pending',
            'proof' => UploadedFile::fake()->image('wrong-amount.jpg'),
        ])->assertSessionHasErrors('amount');
        $this->assertSame(0, $order->payments()->count());

        $this->post($url, ['amount' => 40000])->assertSessionHasErrors('proof');
        $this->assertSame(0, $order->payments()->count());

        $this->post($url, [
            'amount' => 40000,
            'type' => 'other',
            'status' => 'pending',
            'paid_at' => '2000-01-01',
            'proof' => UploadedFile::fake()->image('admin-proof.jpg'),
        ])->assertSessionHasNoErrors();

        $payment = $order->payments()->sole();
        $this->assertSame('dp', $payment->type);
        $this->assertSame('pending', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotSame('2000-01-01 00:00:00', $payment->paid_at->format('Y-m-d H:i:s'));
        $this->assertSame(0.0, $order->fresh()->paid_amount);
        Storage::disk('local')->assertExists($payment->proof_path);
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame(40000.0, $order->fresh()->paid_amount);

        $this->post($url, ['amount' => 60000, 'proof' => UploadedFile::fake()->image('settlement-proof.jpg')])->assertSessionHasNoErrors();
        $settlement = $order->payments()->latest('id')->firstOrFail();
        $this->assertSame('pending', $settlement->status);
        $this->post(route('admin.transactions.review', $settlement), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('lunas', $order->fresh()->paymentStatus->code);
        $this->assertSame(100000.0, $order->fresh()->paid_amount);
    }

    public function test_admin_cannot_duplicate_a_customer_payment_waiting_for_review(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $this->submit($order, $customer)->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.member-orders.payments.store', $order), [
            'amount' => 40000,
            'proof' => UploadedFile::fake()->image('admin-proof.jpg'),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(0.0, $order->fresh()->paid_amount);
    }

    public function test_rejection_requires_reason_preserves_balance_and_customer_can_resubmit(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $this->submit($order, $customer);
        $payment = $order->payments()->first();
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'rejected'])->assertSessionHasErrors('review_notes');
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'rejected', 'review_notes' => 'Nominal pada bukti tidak sesuai.'])->assertSessionHasNoErrors();
        $this->assertSame(100000.0, $order->fresh()->remaining_amount);
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);
        $this->actingAs($customer)->get(route('billing.payments.show', [$order, 'order']))->assertOk()->assertSee('Nominal pada bukti tidak sesuai.');
        $this->submit($order, $customer)->assertSessionHasNoErrors();
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_ems_verification_does_not_pay_order_balance(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $this->submit($order, $customer, ['bill_type' => 'ems', 'amount' => 10000])->assertSessionHasErrors('amount');
        $this->submit($order, $customer, ['bill_type' => 'ems', 'amount' => 20000])->assertSessionHasNoErrors();
        $payment = $order->payments()->first();
        $this->assertSame('ems', $payment->type);
        $this->assertSame('unpaid', $order->fresh()->ems_tax_status);
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->ems_tax_status);
        $this->assertSame(100000.0, $order->fresh()->remaining_amount);
        $this->actingAs($customer)->get(route('billing.index', ['tab' => 'history']))->assertOk()->assertSee('EMS &amp; Pajak', false);
    }

    public function test_duplicate_submissions_and_repeat_reviews_cannot_double_count(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $key = (string) Str::uuid();
        $this->submit($order, $customer, ['submission_key' => $key])->assertSessionHasNoErrors();
        $this->submit($order, $customer, ['submission_key' => $key])->assertSessionHasNoErrors();
        $this->submit($order, $customer)->assertSessionHasErrors('proof');
        $this->assertSame(1, $order->payments()->count());
        $payment = $order->payments()->first();
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasErrors('decision');
        $this->submit($order, $customer, ['submission_key' => $key])->assertSessionHasNoErrors();
        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(40000.0, $order->fresh()->paid_amount);
        $this->assertCount(1, Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_authorization_proof_privacy_and_immutable_customer_records(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $stranger = User::factory()->create(['role' => 'customer', 'member_id' => \App\Models\Member::factory()->create()->id]);
        $this->submit($order, $stranger)->assertForbidden();
        $this->submit($order, $admin)->assertForbidden();
        $this->submit($order, $customer)->assertSessionHasNoErrors();
        $payment = $order->payments()->first();
        $proof = route('billing.payments.proof', [$order, $payment]);
        $this->get($proof)->assertOk();
        $this->actingAs($stranger)->get($proof)->assertForbidden();
        $this->get(route('admin.transactions.index'))->assertForbidden();
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.member-orders.payments.destroy', [$order, $payment]))->assertForbidden();
        $this->put(route('admin.member-orders.payments.update', [$order, $payment]), ['status' => 'verified'])->assertForbidden();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_invalid_upload_and_overpayment_are_rejected_without_files(): void
    {
        [$order, $customer] = $this->setupOrder();
        $this->submit($order, $customer, ['proof' => UploadedFile::fake()->create('bad.html', 2, 'text/html')])->assertSessionHasErrors('proof');
        $this->submit($order, $customer, ['proof' => UploadedFile::fake()->create('big.pdf', 4097, 'application/pdf')])->assertSessionHasErrors('proof');
        $this->submit($order, $customer, ['amount' => 100001])->assertSessionHasErrors('amount');
        $this->assertSame(0, $order->payments()->count());
        $this->assertCount(0, Storage::disk('local')->allFiles('payment-proofs'));
    }

    public function test_customer_timestamp_is_generated_by_server_and_customer_notes_are_ignored(): void
    {
        [$order, $customer] = $this->setupOrder();
        $this->travelTo(now()->setTime(15, 24, 37));
        $this->submit($order, $customer, ['paid_at' => '2000-01-01', 'notes' => 'Injected customer note'])->assertSessionHasNoErrors();
        $payment = $order->payments()->firstOrFail();
        $this->assertTrue($payment->paid_at->equalTo(now()));
        $this->assertNull($payment->notes);
        $key = $payment->submission_key;
        $this->travel(1)->hours();
        $this->submit($order, $customer, ['submission_key' => $key])->assertSessionHasNoErrors();
        $this->assertTrue($payment->fresh()->paid_at->equalTo(now()->subHour()));
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_payment_page_is_private_and_payment_button_navigates_to_it(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);
        $url = route('billing.payments.show', [$order, 'order']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($admin)->get($url)->assertForbidden();
        $other = User::factory()->create(['role' => 'customer', 'member_id' => \App\Models\Member::factory()->create()->id]);
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($customer)->get(route('billing.index'))->assertOk()->assertSee($url)
            ->assertDontSee('name="proof"', false)->assertDontSee('<details class="order-payment-method"', false);
        $this->get($url)->assertOk()->assertSee('Harga keseluruhan')->assertSee('Kirim bukti pembayaran')
            ->assertDontSee('name="paid_at"', false)->assertDontSee('name="notes"', false);
        $this->get(route('billing.payments.show', [$order, 'invalid']))->assertNotFound();
        $this->get(route('billing.payments.show', [$order, 'ems']))->assertOk()->assertSee('EMS &amp; Pajak', false);
        $order->update(['total_amount' => 0]);
        $this->get($url)->assertOk()->assertSee('Tidak ada pembayaran yang perlu diselesaikan')->assertDontSee('name="proof"', false);
    }

    public function test_missing_proof_redirects_to_payment_page_and_view_renders_validation_feedback(): void
    {
        [$order, $customer] = $this->setupOrder();
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);
        $url = route('billing.payments.show', [$order, 'order']);
        $this->actingAs($customer);
        $this->from($url)->post(route('billing.payments.store', $order), [
            'bill_type' => 'order', 'submission_key' => (string) Str::uuid(), 'amount' => 40000,
            'payment_form' => $order->id.'-order',
        ])->assertRedirect($url)->assertSessionHasErrors('proof');
        $this->withViewErrors(['proof' => 'Pilih file bukti pembayaran.'])
            ->view('portal.payment', ['order' => $order, 'billType' => 'order'])
            ->assertSee('Pilih file bukti pembayaran.')->assertSee('pilih kembali file bukti');
    }

    public function test_review_checks_latest_balance_refund_missing_proof_and_ems_allocation(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $this->submit($order, $customer);
        $payment = $order->payments()->first();
        $order->update(['total_amount' => 30000]);
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasErrors('amount');
        $order->update(['total_amount' => 100000, 'payment_status_id' => OrderStatus::factory()->create(['scope' => 'payment', 'code' => 'refund'])->id]);
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasErrors('amount');
        $order->update(['payment_status_id' => null]);
        Storage::disk('local')->delete($payment->proof_path);
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasErrors('decision');
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0.0, $order->fresh()->paid_amount);
    }

    public function test_transaction_filters_search_and_pending_priority(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $this->submit($order, $customer);
        $other = MemberOrder::factory()->create(['order_code' => 'OTHER-TRANSACTION']);
        $other->payments()->create(['type' => 'other', 'amount' => 15000, 'status' => 'verified']);
        $this->actingAs($admin)->get(route('admin.transactions.index'))->assertOk()->assertSeeInOrder([$order->order_code, 'OTHER-TRANSACTION']);
        $this->get(route('admin.transactions.index', ['q' => $order->member->display_name, 'status' => 'pending', 'type' => 'dp', 'date_from' => today()->toDateString(), 'date_to' => today()->toDateString()]))->assertOk()->assertSee($order->order_code)->assertDontSee('OTHER-TRANSACTION');
        $this->get(route('admin.transactions.index', ['status' => 'verified']))->assertOk()->assertSee('OTHER-TRANSACTION')->assertDontSee($order->order_code);
        $this->get(route('admin.transactions.index', ['date_to' => today()->toDateString()]))->assertOk();
    }

    public function test_full_payment_and_pending_upload_ui(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $order->update(['payment_type' => 'full']);
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);
        $this->submit($order, $customer, ['amount' => 100000])->assertSessionHasNoErrors();
        $payment = $order->payments()->first();
        $this->assertSame('full_payment', $payment->type);
        $this->get(route('billing.payments.show', [$order, 'order']))->assertOk()->assertSee('Menunggu verifikasi admin')->assertSee(route('billing.payments.proof', [$order, $payment]));
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('lunas', $order->fresh()->paymentStatus->code);
        $this->assertSame('unpaid', $order->fresh()->ems_tax_status);
    }

    public function test_ems_reallocation_only_charges_difference_from_verified_payments(): void
    {
        [$order, $customer, $admin] = $this->setupOrder();
        $order->payments()->create(['type' => 'ems', 'status' => 'verified', 'amount' => 15000]);
        $this->assertSame(5000.0, $order->fresh()->ems_tax_remaining_amount);
        $this->actingAs($customer)->get(route('billing.index'))->assertOk()->assertViewHas('outstandingTotal', 45000.0);
        $this->submit($order, $customer, ['bill_type' => 'ems', 'amount' => 20000])->assertSessionHasErrors('amount');
        $this->submit($order, $customer, ['bill_type' => 'ems', 'amount' => 5000])->assertSessionHasNoErrors();
        $payment = $order->payments()->latest('id')->first();
        $this->actingAs($admin)->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $order->fresh()->ems_tax_status);
        $this->assertSame(0.0, $order->fresh()->ems_tax_remaining_amount);
        $this->assertSame(0.0, $order->fresh()->paid_amount);
    }

    public function test_failed_database_save_removes_uploaded_file_and_guests_cannot_submit(): void
    {
        [$order, $customer] = $this->setupOrder();
        $this->post(route('billing.payments.store', $order))->assertRedirect(route('login'));
        OrderPayment::creating(fn () => throw new \RuntimeException('Simulated payment write failure'));
        try {
            $this->submit($order, $customer)->assertStatus(500);
        } finally {
            OrderPayment::flushEventListeners();
        }
        $this->assertSame(0, $order->payments()->count());
        $this->assertCount(0, Storage::disk('local')->allFiles('payment-proofs'));
    }
}
