<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private function group(bool $active = true): CustomerGroup
    {
        return CustomerGroup::create(['name' => 'Group '.uniqid(), 'color' => '#7c3aed', 'is_active' => $active]);
    }

    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Customer Baru', 'username' => 'buyer.baru', 'email' => 'buyer@example.test',
            'customer_group_id' => $this->group()->id, 'password' => 'Test-password1!', 'password_confirmation' => 'Test-password1!',
        ], $overrides);
    }

    public function test_registration_creates_customer_without_admin_privileges_then_username_login_works(): void
    {
        $this->post('/register', $this->registration(['username' => ' Buyer.Baru ', 'email' => ' Buyer@Example.Test ']))
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $user = User::where('username', 'buyer.baru')->sole();
        $this->assertSame('customer', $user->role);
        $this->assertNotNull($user->password_set_at);
        $this->assertTrue(Hash::check('Test-password1!', $user->password));
        $this->assertSame('buyer.baru', $user->member->username);
        $this->assertNull($user->line_user_id);
        $this->assertTrue($user->member->isEligibleForNewOrder());
        $this->post('/login', ['username' => ' BUYER.BARU ', 'password' => 'Test-password1!'])->assertRedirect(route('profile.show'));
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/customer-groups')->assertForbidden();
    }

    public function test_inactive_or_missing_group_and_password_confirmation_are_rejected(): void
    {
        $this->get('/register')->assertOk()->assertSee('Belum punya');
        $inactive = $this->group(false);
        $this->get('/register')->assertDontSee($inactive->name);
        $this->post('/register', $this->registration(['customer_group_id' => $inactive->id]))->assertSessionHasErrors('customer_group_id');
        $this->post('/register', $this->registration(['customer_group_id' => 999999]))->assertSessionHasErrors('customer_group_id');
        $this->post('/register', $this->registration(['password_confirmation' => 'different']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_registered_customer_can_be_selected_for_a_new_admin_order(): void
    {
        $this->post('/register', $this->registration())->assertRedirect(route('login'));
        $member = Member::where('username', 'buyer.baru')->sole();
        $batch = Batch::factory()->create();
        $product = Product::factory()->create();
        $batch->products()->attach($product);
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('admin.member-orders.create'))
            ->assertOk()->assertSee('buyer.baru');
        $this->post(route('admin.member-orders.store'), [
            'member_id' => $member->id, 'batch_id' => $batch->id,
            'payment_type' => 'full',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('member_orders', ['member_id' => $member->id, 'batch_id' => $batch->id]);
    }

    public function test_registration_rejects_mismatched_buyer_claim_or_injected_admin_role(): void
    {
        Member::factory()->create(['username' => 'old.buyer', 'email' => 'old@example.test']);
        $this->post('/register', $this->registration(['username' => 'old.buyer']))->assertSessionHasErrors('email');
        $this->post('/register', $this->registration(['email' => 'old@example.test']))->assertSessionHasErrors('username');
        $this->post('/register', $this->registration(['role' => 'admin']))->assertSessionHasErrors('role');
        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_existing_account_username_after_normalization(): void
    {
        User::factory()->create(['username' => 'existing.buyer']);

        $this->post('/register', $this->registration(['username' => ' Existing.Buyer ']))
            ->assertSessionHasErrors(['username' => 'Username LINE sudah memiliki akun login.']);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_registration_claims_existing_member_after_normalization_and_waits_for_approval(): void
    {
        $member = Member::factory()->create(['username' => 'existing.buyer', 'email' => 'existing@example.test']);

        $this->post('/register', $this->registration(['username' => ' Existing.Buyer ', 'email' => 'existing@example.test']))
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('members', 1);
        $user = User::where('username', 'existing.buyer')->sole();
        $this->assertSame($member->id, $user->member_id);
        $this->assertTrue($user->registration_pending);
    }

    public function test_registration_requires_every_password_strength_category(): void
    {
        foreach (['Aa1!abc', 'weak-password1!', 'WEAK-PASSWORD1!', 'Weak-password!', 'Weakpassword1'] as $password) {
            $this->post('/register', $this->registration([
                'password' => $password,
                'password_confirmation' => $password,
            ]))->assertSessionHasErrors('password');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('members', 0);
    }

    public function test_registration_form_explains_password_requirements(): void
    {
        $this->get('/register')->assertOk()
            ->assertSee('Username LINE harus unik.')
            ->assertSee('Belum diisi: 0 dari 6 syarat terpenuhi')
            ->assertSee('data-password-rule="uppercase"', false)
            ->assertSee('data-password-rule="symbol"', false)
            ->assertSee('data-password-rule="confirmation"', false)
            ->assertSeeInOrder(['name="password_confirmation"', 'data-password-monitor'], false)
            ->assertSee('aria-describedby="password-requirements"', false);
    }

    public function test_wrong_password_and_inactive_customer_cannot_login_and_existing_session_is_revoked(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $member->id]);
        $this->post('/login', ['username' => $user->username, 'password' => 'wrong'])->assertSessionHasErrors('username');
        $member->update(['is_active' => false]);
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->actingAs($user)->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_activate_old_buyer_without_losing_orders_then_change_credentials(): void
    {
        $member = Member::factory()->create(['username' => 'legacy.buyer']);
        $order = MemberOrder::factory()->create(['member_id' => $member->id]);
        $oldUser = User::factory()->create(['role' => 'customer', 'member_id' => $member->id, 'password_set_at' => null, 'line_user_id' => 'old-line-id']);
        $admin = User::factory()->create();
        $group = $this->group();
        $data = ['display_name' => 'Buyer Lama', 'username' => 'legacy.buyer', 'email' => 'legacy@example.test', 'is_active' => '1', 'customer_group_id' => $group->id, 'password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->actingAs($admin)->put(route('admin.members.update', $member), $data)->assertRedirect();
        $this->assertDatabaseCount('members', 1);
        $this->assertSame($member->id, $order->fresh()->member_id);
        $this->assertSame($oldUser->id, $member->fresh()->user->id);
        $this->assertTrue($member->fresh()->isEligibleForNewOrder());
        $this->assertTrue(Hash::check('new-password', $oldUser->fresh()->password));
        unset($data['password'], $data['password_confirmation']);
        $data['username'] = 'renamed.buyer';
        $this->put(route('admin.members.update', $member), $data)->assertRedirect();
        $this->assertSame('renamed.buyer', $oldUser->fresh()->username);
        $this->assertTrue(Hash::check('new-password', $oldUser->fresh()->password));
        $this->post('/logout')->assertRedirect();
        $this->post('/login', ['username' => 'renamed.buyer', 'password' => 'new-password'])->assertRedirect(route('profile.show'));
    }

    public function test_admin_group_management_and_archiving_preserve_membership(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.customer-groups.store'), ['name' => 'Group A', 'color' => '#123456', 'is_active' => '1'])->assertRedirect();
        $group = CustomerGroup::where('name', 'Group A')->sole();
        $member = Member::factory()->create(['customer_group_id' => $group->id]);
        $this->get(route('admin.customer-groups.show', $group))->assertOk()->assertSee($member->display_name);
        $this->get(route('admin.members.index', ['customer_group_id' => $group->id]))->assertOk()->assertSee('Group A');
        $this->get(route('admin.members.show', $member))->assertOk()->assertSee('Group A');
        $this->delete(route('admin.customer-groups.destroy', $group))->assertRedirect();
        $this->assertFalse($group->fresh()->is_active);
        $this->assertSame($group->id, $member->fresh()->customer_group_id);
        $this->put(route('admin.customer-groups.update', $group), ['name' => 'Group A', 'color' => '#123456', 'is_active' => '1'])->assertRedirect();
        $this->assertTrue($group->fresh()->is_active);
    }

    public function test_tracking_payment_and_group_tabs_keep_their_data_separate(): void
    {
        $admin = User::factory()->create();
        $tracking = OrderStatus::factory()->create(['scope' => 'all']);
        $payment = OrderStatus::factory()->create(['scope' => 'payment']);
        $group = $this->group();
        $this->actingAs($admin)->get(route('admin.order-statuses.index', ['scope' => 'groups']))->assertOk()
            ->assertSee('data-status-folder-tab="tracking"', false)->assertSee('data-status-folder-tab="payment"', false)
            ->assertDontSee('data-status-folder-tab="groups"', false)->assertDontSee($group->name);
        $this->get(route('admin.customer-groups.index'))->assertOk()->assertSee($group->name);
        $this->assertFalse($tracking->isUsableFor('payment'));
        $this->assertFalse($payment->isUsableFor('batch'));
        $this->assertSame([$payment->id], OrderStatus::activeFor('payment')->pluck('id')->all());
    }

    public function test_admin_email_login_remains_available_and_old_line_urls_no_longer_authenticate(): void
    {
        $admin = User::factory()->create();
        $this->get('/auth/line')->assertRedirect('/login');
        $this->get('/auth/line/callback?code=fake')->assertRedirect('/login');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_shared_login_accepts_admin_username_and_email_and_redirects_by_role(): void
    {
        $admin = User::factory()->create(['username' => 'shared.admin']);
        $this->get('/admin/login')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Username LINE atau email')->assertDontSee('Login admin');

        foreach ([$admin->username, $admin->email] as $identifier) {
            $this->withSession(['url.intended' => route('profile.show')])
                ->post('/login', ['username' => ' '.strtoupper($identifier).' ', 'password' => 'password'])
                ->assertRedirect(route('admin.dashboard'));
            $this->assertAuthenticatedAs($admin);
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_shared_login_accepts_customer_email_and_does_not_redirect_to_admin(): void
    {
        $this->post('/register', $this->registration())->assertRedirect(route('login'));
        $user = User::where('username', 'buyer.baru')->sole();
        $this->withSession(['url.intended' => route('admin.dashboard')])
            ->post('/login', ['username' => ' BUYER@EXAMPLE.TEST ', 'password' => 'Test-password1!'])
            ->assertRedirect(route('profile.show'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
    }
}
