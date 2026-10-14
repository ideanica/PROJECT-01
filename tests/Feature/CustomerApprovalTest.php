<?php

namespace Tests\Feature;

use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function registerPending(): User
    {
        $this->post('/register', [
            'name' => 'Pending Buyer', 'username' => 'pending.buyer', 'email' => 'pending@example.test',
            'customer_group_id' => '', 'password' => 'Test-password1!', 'password_confirmation' => 'Test-password1!',
        ])->assertRedirect(route('login'))->assertSessionHas('status', fn ($message) => str_contains($message, 'Tunggu persetujuan admin'));

        return User::where('username', 'pending.buyer')->sole();
    }

    public function test_registration_without_any_groups_waits_for_admin_and_cannot_login_or_order(): void
    {
        $this->get('/register')->assertOk()->assertSee('Belum punya')->assertSee('Daftar akun');
        $user = $this->registerPending();
        $this->assertTrue($user->registration_pending);
        $this->assertNull($user->member->customer_group_id);
        $this->assertFalse($user->member->is_active);
        $this->assertFalse($user->member->isEligibleForNewOrder());
        $this->assertFalse(Member::eligibleForNewOrder()->whereKey($user->member_id)->exists());
        $this->post('/login', ['username' => $user->username, 'password' => 'Test-password1!'])->assertSessionHasErrors('username');
        $this->assertGuest();
        $this->actingAs($user)->get('/profile')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_assigns_active_group_and_accepts_account_once(): void
    {
        $user = $this->registerPending();
        $memberId = $user->member_id;
        $group = CustomerGroup::create(['name' => 'Accepted Group', 'color' => '#355a45', 'is_active' => true]);
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('admin.members.index', ['registration' => 'pending']))->assertOk()->assertSee('Pending Buyer')->assertSee('Permintaan registrasi (1)');
        $this->get(route('admin.members.show', $memberId))->assertOk()->assertSee('Tentukan group');
        $this->post(route('admin.members.approve', $memberId), ['customer_group_id' => $group->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertFalse($user->fresh()->registration_pending);
        $this->assertSame($group->id, $user->fresh()->member->customer_group_id);
        $this->assertTrue($user->fresh()->member->isEligibleForNewOrder());
        $this->assertDatabaseCount('members', 1);
        $this->post(route('admin.members.approve', $memberId), ['customer_group_id' => $group->id])->assertStatus(409);
        $this->get(route('admin.customer-groups.index'))->assertOk()->assertSee('Jumlah anggota')->assertSee('Accepted Group');
        $this->get(route('admin.members.index', ['registration' => 'pending']))->assertOk()->assertDontSee('Pending Buyer');
        $this->post('/logout');
        $this->post('/login', ['username' => $user->username, 'password' => 'Test-password1!'])->assertRedirect(route('profile.show'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('admin.members.approve', $memberId), ['customer_group_id' => $group->id])->assertForbidden();
    }

    public function test_missing_or_inactive_group_cannot_approve_and_edit_cannot_bypass_approval(): void
    {
        $user = $this->registerPending();
        $inactive = CustomerGroup::create(['name' => 'Closed', 'color' => '#355a45', 'is_active' => false]);
        $this->actingAs(User::factory()->create());
        foreach (['', 99999, $inactive->id] as $id) {
            $this->post(route('admin.members.approve', $user->member_id), ['customer_group_id' => $id])->assertSessionHasErrors('customer_group_id');
        }
        $this->put(route('admin.members.update', $user->member_id), [
            'display_name' => 'Pending Buyer', 'username' => $user->username, 'email' => $user->email, 'is_active' => '1',
        ])->assertRedirect();
        $this->assertTrue($user->fresh()->registration_pending);
        $this->assertFalse($user->fresh()->member->is_active);
    }

    public function test_group_counts_include_all_assigned_buyers_and_labels_are_outside_status(): void
    {
        $group = CustomerGroup::create(['name' => 'Counted Group', 'color' => '#355a45', 'is_active' => true]);
        Member::factory()->count(2)->create(['customer_group_id' => $group->id]);
        Member::factory()->create(['customer_group_id' => $group->id, 'is_active' => false]);
        Member::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('admin.members.index'))->assertOk()->assertSee('Counted Group · 3 anggota');
        $this->get(route('admin.customer-groups.index'))->assertOk()->assertSee('Jumlah anggota')->assertSee('Counted Group');
        $this->get(route('admin.order-statuses.index'))->assertOk()->assertDontSee('data-status-folder-tab="groups"', false)->assertDontSee('Counted Group');
    }
}
