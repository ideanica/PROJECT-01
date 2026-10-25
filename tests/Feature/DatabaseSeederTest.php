<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\ShipmentGroup;
use App\Models\StatusHistory;
use App\Models\User;
use Database\Seeders\DemoBusinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_builds_a_repeatable_business_scenario_with_email_accounts(): void
    {
        Config::set('seeders.admin', [
            'name' => 'OceanPaws Admin Demo',
            'username' => null,
            'email' => 'admin.demo@oceanpaws.test',
            'password' => 'password',
        ]);
        Config::set('seeders.demo_customer_email', 'customer.demo@oceanpaws.test');
        Config::set('seeders.demo_customer_password', 'password');

        $this->seed(DemoBusinessSeeder::class);

        $admin = User::query()->where('email', 'admin.demo@oceanpaws.test')->sole();
        $customer = User::query()->where('email', 'customer.demo@oceanpaws.test')->sole();
        $member = Member::query()->whereKey($customer->member_id)->sole();
        $paidOrder = MemberOrder::query()->where('order_code', 'OP-DEMO-ORDER-PAID')->with('paymentStatus')->sole();
        $unpaidOrder = MemberOrder::query()->where('order_code', 'OP-DEMO-ORDER-UNPAID')->with('paymentStatus')->sole();
        $dpOrder = MemberOrder::query()->where('order_code', 'OP-DEMO-ORDER-DP')->with('paymentStatus')->sole();
        $historicalOrder = MemberOrder::query()->where('order_code', 'OP-DEMO-ORDER-HISTORY')->with(['overrideStatus', 'payments'])->sole();
        $counts = [
            'users' => User::count(),
            'members' => Member::count(),
            'groups' => $member->customerGroup()->count(),
            'products' => Product::count(),
            'batches' => Batch::count(),
            'orders' => MemberOrder::count(),
            'payments' => OrderPayment::count(),
            'histories' => StatusHistory::count(),
            'shipments' => ShipmentGroup::count(),
        ];

        $this->assertTrue($admin->isAdmin());
        $this->assertNull($admin->username);
        $this->assertTrue(password_verify('password', $admin->password));
        $this->assertSame(User::ROLE_CUSTOMER, $customer->role);
        $this->assertNull($customer->username);
        $this->assertTrue(password_verify('password', $customer->password));
        $this->assertSame('OP-CUSTOMER-DEMO', $member->member_code);
        $this->assertSame(3, Product::count());
        $this->assertSame(6, Batch::count());
        $this->assertSame(5, MemberOrder::count());
        $this->assertSame(7, OrderPayment::count());
        $this->assertGreaterThanOrEqual(6, StatusHistory::count());
        $this->assertSame(1, ShipmentGroup::count());
        $this->assertSame(40.0, (float) PaymentSetting::findOrFail(1)->dp_percentage);
        $this->assertSame('lunas', $paidOrder->paymentStatus->code);
        $this->assertSame('menunggu-dp', $unpaidOrder->paymentStatus->code);
        $this->assertSame('menunggu-pelunasan', $dpOrder->paymentStatus->code);
        $this->assertSame(150000.0, $paidOrder->paid_amount);
        $this->assertSame(0.0, $unpaidOrder->paid_amount);
        $this->assertSame(120000.0, $dpOrder->paid_amount);
        $this->assertSame('selesai', $historicalOrder->overrideStatus->code);
        $this->assertSame(2, $historicalOrder->items()->sole()->quantity);
        $this->assertSame(1, OrderPayment::query()->where('status', 'pending')->count());
        $this->assertSame(1, OrderPayment::query()->where('status', 'rejected')->count());
        $this->assertSame(1, Batch::query()->where('batch_number', 'OP-DEMO-CATALOG-OPEN')->where('is_catalog_visible', true)->count());

        $status = OrderStatus::query()->where('code', 'menunggu-dp')->sole();
        $status->update(['name' => 'Nama status yang diatur admin', 'color' => '#123456']);
        $this->seed(DemoBusinessSeeder::class);

        $this->assertSame($counts, [
            'users' => User::count(),
            'members' => Member::count(),
            'groups' => $member->customerGroup()->count(),
            'products' => Product::count(),
            'batches' => Batch::count(),
            'orders' => MemberOrder::count(),
            'payments' => OrderPayment::count(),
            'histories' => StatusHistory::count(),
            'shipments' => ShipmentGroup::count(),
        ]);
        $this->assertSame('Nama status yang diatur admin', $status->fresh()->name);
        $this->assertSame('#123456', $status->fresh()->color);
    }
}
