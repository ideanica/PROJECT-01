<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_group_label_uses_the_assigned_group_and_handles_missing_group(): void
    {
        $group = CustomerGroup::create(['name' => 'Group Profil']);
        $member = Member::factory()->create(['customer_group_id' => $group->id]);
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);

        $this->actingAs($user)->get(route('profile.show'))->assertOk()
            ->assertSee('profile-group-pill', false)->assertSee('Group Profil');

        $member->update(['customer_group_id' => null]);
        $user->unsetRelation('member');
        $this->get(route('profile.show'))->assertOk()->assertSee('Belum ada group')->assertDontSee('Group Profil');
    }

    public function test_guest_profile_keeps_the_customer_layout_with_an_empty_avatar_and_login_label(): void
    {
        $this->get(route('profile.show'))
            ->assertOk()
            ->assertSee('profile-photo is-empty', false)
            ->assertSee('>Login</strong>', false)
            ->assertSee('MASUK KE AKUN')
            ->assertDontSee('DATA MILIKMU')
            ->assertDontSee('data-profile-settings-button', false)
            ->assertSee(route('login'), false);
    }

    public function test_full_order_history_stays_private_while_profile_shows_this_months_snacks(): void
    {
        $member = Member::factory()->create(['display_name' => 'Caca Member']);
        $otherMember = Member::factory()->create(['display_name' => 'Member Lain']);
        $user = User::factory()->create([
            'role' => 'customer',
            'member_id' => $member->id,
            'line_user_id' => 'U-profile-owner',
        ]);

        foreach (range(1, 6) as $index) {
            $order = MemberOrder::factory()->create([
                'member_id' => $member->id,
                'batch_id' => Batch::factory()->create([
                    'batch_name' => 'Batch Milik Caca '.$index,
                ])->id,
                'order_code' => 'OWN-ORDER-'.$index,
                'payment_type' => 'dp',
                'payment_amount' => 100000,
                'total_amount' => 200000,
            ]);

            OrderItem::factory()->create([
                'member_order_id' => $order->id,
                'item_name' => 'Album Caca '.$index,
                'variant' => 'Versi '.$index,
                'quantity' => 1,
                'unit_price' => 200000,
                'subtotal' => 200000,
            ]);
        }

        MemberOrder::factory()->create([
            'member_id' => $otherMember->id,
            'batch_id' => Batch::factory()->create(['batch_name' => 'Batch Rahasia Member Lain'])->id,
            'order_code' => 'OTHER-PRIVATE-ORDER',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Caca Member')
            ->assertSee('data-profile-settings-button', false)
            ->assertSee('data-profile-language-switch', false)
            ->assertSee('data-profile-theme-switch', false)
            ->assertSee('Keluar dari akun')
            ->assertSee(route('orders.history'), false)
            ->assertDontSee('DATA MILIKMU')
            ->assertDontSee('OWN-ORDER-1')
            ->assertSee('Album Caca 6');

        $this->actingAs($user)
            ->get(route('orders.history'))
            ->assertOk()
            ->assertSee('Riwayat pesanan')
            ->assertSee('OWN-ORDER-1')
            ->assertSee('OWN-ORDER-6')
            ->assertSee('Album Caca 6')
            ->assertSee('Versi 6')
            ->assertDontSee('OTHER-PRIVATE-ORDER')
            ->assertDontSee('Batch Rahasia Member Lain');
    }

    public function test_calendar_month_changes_the_snack_list_without_showing_another_members_items(): void
    {
        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
        $thisMonth = now()->startOfMonth();
        $lastMonth = $thisMonth->copy()->subMonth();

        $thisMonthOrder = MemberOrder::factory()->create([
            'member_id' => $member->id,
            'created_at' => $thisMonth->copy()->addDays(4),
        ]);
        $lastMonthOrder = MemberOrder::factory()->create([
            'member_id' => $member->id,
            'created_at' => $lastMonth->copy()->addDays(6),
        ]);
        $otherOrder = MemberOrder::factory()->create([
            'member_id' => $otherMember->id,
            'created_at' => $lastMonth->copy()->addDays(6),
        ]);

        OrderItem::factory()->create(['member_order_id' => $thisMonthOrder->id, 'item_name' => 'Album Mint September', 'quantity' => 2]);
        OrderItem::factory()->create(['member_order_id' => $lastMonthOrder->id, 'item_name' => 'Photocard Peach August', 'quantity' => 3]);
        OrderItem::factory()->create(['member_order_id' => $otherOrder->id, 'item_name' => 'Jajanan Rahasia Member Lain']);

        $this->actingAs($user)
            ->get(route('profile.show', ['month' => $thisMonth->format('Y-m')]))
            ->assertOk()
            ->assertSee('Album Mint September')
            ->assertDontSee('Photocard Peach August')
            ->assertDontSee('Jajanan Rahasia Member Lain')
            ->assertSee('class="chart-line-plot"', false)
            ->assertSee(route('profile.show', ['month' => $lastMonth->format('Y-m')]), false);

        $this->actingAs($user)
            ->get(route('profile.show', ['month' => $lastMonth->format('Y-m')]))
            ->assertOk()
            ->assertSee('Photocard Peach August')
            ->assertDontSee('Album Mint September')
            ->assertDontSee('Jajanan Rahasia Member Lain');
    }

    public function test_member_can_only_open_their_own_payment_proof(): void
    {
        Storage::fake('local');

        $member = Member::factory()->create();
        $otherMember = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);

        $ownOrder = MemberOrder::factory()->create([
            'member_id' => $member->id,
            'payment_proof_path' => 'catalog/payment-proofs/own.jpg',
        ]);
        $otherOrder = MemberOrder::factory()->create([
            'member_id' => $otherMember->id,
            'payment_proof_path' => 'catalog/payment-proofs/other.jpg',
        ]);

        Storage::disk('local')->put($ownOrder->payment_proof_path, 'own-proof');
        Storage::disk('local')->put($otherOrder->payment_proof_path, 'other-proof');

        $this->actingAs($user)
            ->get(route('profile.orders.payment-proof', $ownOrder))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('profile.orders.payment-proof', $otherOrder))
            ->assertForbidden();
    }
}
