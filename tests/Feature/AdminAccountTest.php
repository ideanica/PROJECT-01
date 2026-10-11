<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_management_endpoints_are_removed(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get('/admin/admin-users')->assertNotFound();
        $this->post('/admin/admin-users', ['name' => 'Another admin', 'email' => 'new.admin@example.test'])->assertNotFound();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Akun Admin');
        $this->get(route('profile.show'))->assertOk()->assertDontSee('Kelola akun admin')->assertDontSee('SUPERADMIN');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_role_migration_preserves_admin_credentials_and_customer_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin']);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $password = $admin->password;
        $migration = require database_path('migrations/2026_10_11_000001_restore_admin_role.php');
        $migration->up();
        $migration->up();
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertSame($password, $admin->fresh()->password);
        $this->assertSame(User::ROLE_CUSTOMER, $customer->fresh()->role);
        $this->post('/login', ['username' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    }
    public function test_admin_and_customer_have_role_specific_settings_and_edit_forms(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('profile.show'))->assertOk()
            ->assertSee('Halaman admin')->assertSee('Nama, email, dan password')
            ->assertSee('data-profile-settings-button', false)->assertDontSee('profile-group-pill', false);
        $this->get(route('profile.edit'))->assertOk()->assertSee('name="name"', false)
            ->assertDontSee('name="phone"', false)->assertDontSee('name="username"', false);
        $member = Member::factory()->create();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'member_id' => $member->id]);
        $this->actingAs($customer)->get(route('profile.show'))->assertOk()
            ->assertSee('profile-group-pill', false)->assertSee('data-profile-settings-button', false)->assertDontSee('Halaman admin');
        $this->get(route('profile.edit'))->assertOk()->assertSee('name="phone"', false);
        $this->put(route('profile.admin.update'), ['name' => 'Invalid', 'email' => $customer->email])->assertForbidden();
    }

    public function test_admin_profile_requires_current_password_for_sensitive_changes_and_preserves_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->put(route('profile.admin.update'), ['name' => 'New Name', 'email' => $admin->email])
            ->assertSessionHasNoErrors();
        $this->put(route('profile.admin.update'), ['name' => 'New Name', 'email' => 'changed@example.test'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('profile.admin.update'), ['name' => 'New Name', 'email' => 'changed@example.test', 'current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('profile.admin.update'), ['name' => 'New Name', 'email' => 'changed@example.test', 'current_password' => 'password',
            'password' => 'New-password1!', 'password_confirmation' => 'New-password1!'])->assertSessionHasNoErrors();
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertTrue(Hash::check('New-password1!', $admin->fresh()->password));
        $this->post('/logout');
        $this->post('/login', ['username' => 'changed@example.test', 'password' => 'New-password1!'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_stale_session_cannot_authenticate_as_another_database_identity(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'member_id' => Member::factory()->create()->id]);
        $admin = User::factory()->create();
        $this->withSession([Auth::guard()->getName() => $admin->id, 'auth_identity' => $customer->sessionIdentity()])
            ->get(route('profile.show'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_seeder_preserves_existing_credentials_and_does_not_create_another_admin(): void
    {
        $this->seed(AdminUserSeeder::class);
        $user = User::where('email', 'admin@example.com')->sole();
        $this->assertTrue($user->isAdmin());
        $user->password = 'Changed-password1!';
        $user->email = 'changed.admin@example.test';
        $user->save();
        $password = $user->password;
        $this->seed(AdminUserSeeder::class);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertTrue($user->fresh()->isAdmin());
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
    }

    public function test_demo_readiness_repair_preserves_password_and_does_not_activate_real_line_accounts(): void
    {
        $demo = Member::factory()->create(['member_code' => 'OP-CUSTOMER-DEMO']);
        $user = User::factory()->create(['role' => 'customer', 'member_id' => $demo->id,
            'email' => 'customer.demo@oceanpaws.test', 'password_set_at' => null]);
        $password = $user->password;
        $other = User::factory()->create(['role' => 'customer', 'member_id' => Member::factory()->create()->id, 'password_set_at' => null]);
        $migration = require database_path('migrations/2026_10_10_000004_restore_demo_customer_password_readiness.php');
        $migration->up();
        $this->assertNotNull($user->fresh()->password_set_at);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertNull($other->fresh()->password_set_at);
    }
}
