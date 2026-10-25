<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Database\Seeders\CustomerDemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CustomerDemoAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_demo_seeder_creates_a_ready_customer_and_does_not_reset_existing_passwords(): void
    {
        Config::set('seeders.demo_customer_password', 'password');

        $this->seed(CustomerDemoAccountSeeder::class);

        $user = User::query()->where('email', 'customer.demo@oceanpaws.test')->sole();
        $member = Member::query()->whereKey($user->member_id)->sole();

        $this->assertSame(User::ROLE_CUSTOMER, $user->role);
        $this->assertFalse($user->registration_pending);
        $this->assertNotNull($user->password_set_at);
        $this->assertTrue($member->is_active);
        $this->assertTrue(password_verify('password', $user->password));

        $user->update(['password' => 'changed-password']);
        $this->seed(CustomerDemoAccountSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'customer.demo@oceanpaws.test')->count());
        $this->assertSame(1, Member::query()->where('email', 'customer.demo@oceanpaws.test')->count());
        $this->assertNull($user->fresh()->username);
        $this->assertTrue(password_verify('changed-password', $user->fresh()->password));
    }
}
