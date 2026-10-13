<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerCredentialsMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Exercise table rebuilding outside the per-test transaction.
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate')->assertExitCode(0);
    }

    public function test_upgrade_and_rollback_keep_existing_buyer_orders_and_admin_passwords(): void
    {
        $member = Member::factory()->create(['username' => 'existing.buyer']);
        $order = MemberOrder::factory()->create(['member_id' => $member->id]);
        $oldUser = User::factory()->create(['role' => 'member', 'member_id' => $member->id, 'line_user_id' => 'legacy-line']);
        $admin = User::factory()->create();
        $adminPassword = $admin->password;
        DB::table('members')->where('id', $member->id)->update(['group_name' => 'Group Lama']);
        $migration = require database_path('migrations/2026_10_08_000001_add_customer_credentials_and_groups.php');
        $migration->down();
        $migration->up();
        $this->assertSame('customer', $oldUser->fresh()->role);
        $this->assertSame('existing.buyer', $oldUser->fresh()->username);
        $this->assertNull($oldUser->fresh()->password_set_at);
        $this->assertSame($adminPassword, $admin->fresh()->password);
        $this->assertNotNull($admin->fresh()->password_set_at);
        $this->assertSame($member->id, $order->fresh()->member_id);
        $this->assertFalse($member->fresh()->isEligibleForNewOrder());
        $this->assertSame('Group Lama', $member->fresh()->customerGroup->name);
        $this->assertSame('customer', DB::table('users')->where('id', $oldUser->id)->value('role'));
    }
}
