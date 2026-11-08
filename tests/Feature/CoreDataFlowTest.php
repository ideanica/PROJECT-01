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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoreDataFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_order_payment_and_admin_review_share_the_same_records(): void
    {
        Config::set('seeders.admin', ['name' => null, 'username' => null, 'email' => null, 'password' => null]);
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        PaymentSetting::findOrFail(1)->update(['dp_percentage' => 40]);
        PaymentMethod::create(['name' => 'QRIS', 'type' => 'qris', 'is_active' => true]);
        $member = Member::factory()->create(['is_active' => true]);
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER, 'member_id' => $member->id,
            'password_set_at' => now(), 'registration_pending' => false,
        ]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $batch = Batch::factory()->create([
            'current_status_id' => OrderStatus::where('code', 'menunggu-pemesanan')->sole()->id,
            'is_catalog_visible' => true, 'ordering_deadline' => now()->addDay(),
        ]);
        $product = Product::factory()->create(['default_price' => 100000]);
        $batch->products()->attach($product, ['full_price' => 100000, 'dp_price' => 40000, 'is_available' => true]);

        $order = MemberOrder::query()->create([
            'order_code' => 'TEST-DATA-FLOW',
            'member_id' => $member->id,
            'batch_id' => $batch->id,
            'order_source' => 'admin',
            'payment_type' => 'dp',
            'dp_percentage' => 40,
            'total_amount' => 100000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'item_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);
        app(\App\Services\PaymentVerificationService::class)->sync($order);

        $this->actingAs($customer)->get(route('billing.payments.show', [$order, 'order']))->assertOk();
        $this->assertSame('100000.00', $order->fresh()->total_amount);
        $this->assertSame('40.00', $order->fresh()->dp_percentage);

        $this->actingAs($customer)->post(route('billing.payments.store', $order), [
            'bill_type' => 'order',
            'submission_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount' => 40000,
            'proof' => UploadedFile::fake()->image('payment.png'),
        ])->assertRedirect();
        $payment = $order->payments()->sole();
        $this->assertSame('pending', $payment->status);
        $this->assertSame(0.0, $order->fresh()->paid_amount);

        $this->actingAs($admin)->get(route('admin.transactions.show', $payment))->assertOk()
            ->assertSee($member->display_name)->assertSee($order->order_code);
        $this->post(route('admin.transactions.review', $payment), ['decision' => 'verified'])->assertRedirect();

        $this->assertSame('verified', $payment->fresh()->status);
        $this->assertSame(40000.0, $order->fresh()->paid_amount);
        $this->assertSame('menunggu-pelunasan', $order->fresh()->paymentStatus->code);
        $this->actingAs($customer)->get(route('billing.index', ['tab' => 'history']))->assertOk()
            ->assertSee($order->order_code)->assertSee('Rp 40.000')->assertSee('Terverifikasi');
    }
}
