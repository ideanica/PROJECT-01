<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\StatusHistory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_creates_only_system_statuses_and_is_repeatable(): void
    {
        Config::set('seeders.admin', ['name' => null, 'username' => null, 'email' => null, 'password' => null]);

        $this->seed(DatabaseSeeder::class);
        $statusCount = OrderStatus::count();
        $status = OrderStatus::where('code', 'menunggu-dp')->sole();
        $status->update(['name' => 'Nama status yang diatur admin', 'color' => '#123456']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($statusCount, OrderStatus::count());
        $this->assertSame('Nama status yang diatur admin', $status->fresh()->name);
        $this->assertSame('#123456', $status->fresh()->color);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(0, Member::count());
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Batch::count());
        $this->assertSame(0, MemberOrder::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, OrderPayment::count());
        $this->assertSame(0, StatusHistory::count());
    }
}
