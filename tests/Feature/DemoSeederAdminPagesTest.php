<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\User;
use Database\Seeders\DemoBusinessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DemoSeederAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_accounts_can_sign_in_and_admin_pages_show_linked_demo_records(): void
    {
        Config::set('seeders.admin', [
            'name' => 'OceanPaws Admin',
            'username' => null,
            'email' => 'admin.demo@oceanpaws.test',
            'password' => 'password',
        ]);
        Config::set('seeders.demo_customer_email', 'customer.demo@oceanpaws.test');
        Config::set('seeders.demo_customer_password', 'password');
        $this->seed(DemoBusinessSeeder::class);

        $admin = User::query()->where('email', 'admin.demo@oceanpaws.test')->sole();
        $this->get(route('login'))->assertOk()->assertSee('Email')->assertDontSee('Username LINE atau email');
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $historyBatch = Batch::query()->where('batch_number', 'OP-GO-2025-PLUSH')->sole();
        $historyOrder = MemberOrder::query()->where('order_code', 'OP-2025-0981')->sole();
        $pendingPayment = OrderPayment::query()->where('status', 'pending')->sole();

        foreach ([
            route('admin.dashboard'),
            route('admin.customer-groups.index'),
            route('admin.members.index'),
            route('admin.products.index'),
            route('admin.batches.index'),
            route('admin.member-orders.index'),
            route('admin.transactions.index'),
            route('admin.order-statuses.index'),
            route('admin.status-histories.index'),
            route('admin.batches.settings'),
            route('admin.batches.shipments.index', $historyBatch),
            route('admin.member-orders.show', $historyOrder),
            route('admin.transactions.show', $pendingPayment),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $customer = User::query()->where('email', 'customer.demo@oceanpaws.test')->sole();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('profile.show'));
        $this->get(route('orders.history'))->assertOk()->assertSee('OP-2025-0981');
        $this->get(route('billing.index'))->assertOk()->assertSee('OP-2026-1002');
        $this->get(route('catalog.index'))->assertOk()->assertSee('Official Lightstick GO 2026');
    }
}
