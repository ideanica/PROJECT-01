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

    public function test_customer_can_search_and_sort_only_their_own_orders(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
        $batch = Batch::factory()->create(['batch_name' => 'Searchable merchandise']);
        foreach (['SEARCH-LOW' => 100000, 'SEARCH-HIGH' => 300000] as $code => $amount) {
            MemberOrder::factory()->create([
                'member_id' => $member->id,
                'batch_id' => Batch::factory()->create(['batch_name' => 'Searchable merchandise'])->id,
                'order_code' => $code, 'total_amount' => $amount,
            ]);
        }
        MemberOrder::factory()->create(['batch_id' => $batch->id, 'order_code' => 'SEARCH-PRIVATE']);

        foreach (['orders.history', 'billing.index'] as $route) {
            $this->actingAs($user)->get(route($route, ['q' => 'searchable', 'sort' => 'amount_desc']))
                ->assertOk()->assertSeeInOrder(['SEARCH-HIGH', 'SEARCH-LOW'])
                ->assertDontSee('SEARCH-PRIVATE');
            $this->get(route($route, ['q' => 'SEARCH-LOW']))
                ->assertOk()->assertSee('SEARCH-LOW')->assertDontSee('SEARCH-HIGH');
            $this->get(route($route, ['q' => 'no matching order']))
                ->assertOk()->assertDontSee('SEARCH-LOW')->assertDontSee('SEARCH-HIGH');
        }
    }

    public function test_payment_form_reopens_and_keeps_input_after_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = MemberOrder::factory()->create(['total_amount' => 100000]);
        $url = route('admin.member-orders.payments.index', $order);

        $this->actingAs($admin)->from($url)->post(route('admin.member-orders.payments.store', $order), [
            'amount' => -1,
            'notes' => 'Keep this payment note',
        ])->assertRedirect($url)->assertSessionHasErrors('amount');
        $response = $this->get($url)->assertOk()->assertSee('Keep this payment note')
            ->assertSee('Kirim bukti pembayaran di luar transaksi customer');
        $this->assertMatchesRegularExpression('/<details[^>]*\sopen(?:\s|>)/', $response->getContent());
    }

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
        $order = MemberOrder::factory()->create(['member_id' => $member->id, 'batch_id' => $batch->id, 'payment_status_id' => $status->id, 'total_amount' => 300000]);
        $this->actingAs($user)->get(route('billing.payments.show', [$order, 'order']))->assertOk()
            ->assertSee('QRIS Ocean Paws')->assertSee('Gunakan kode pesanan saat konfirmasi.');
    }

}
