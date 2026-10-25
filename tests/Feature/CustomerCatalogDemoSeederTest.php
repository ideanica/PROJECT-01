<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\StatusHistory;
use Database\Seeders\CustomerCatalogDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCatalogDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_catalog_has_traceable_products_status_and_history_without_fake_orders(): void
    {
        $this->seed(CustomerCatalogDemoSeeder::class);
        $this->seed(CustomerCatalogDemoSeeder::class);

        $batches = Batch::query()
            ->whereIn('batch_number', ['OP-GO-CATALOG-OPEN', 'OP-GO-CATALOG-CLOSED'])
            ->with(['currentStatus', 'products', 'statusHistories'])
            ->get();

        $this->assertCount(2, $batches);
        $this->assertSame(2, Product::query()->whereIn('name', ['Official Lightstick', 'Photocard Set'])->count());
        $this->assertSame(2, StatusHistory::query()->where('trackable_type', Batch::class)->count());
        $this->assertSame(0, MemberOrder::query()->count());

        foreach ($batches as $batch) {
            $this->assertTrue($batch->is_catalog_visible);
            $this->assertSame('menunggu-pemesanan', $batch->currentStatus->code);
            $this->assertCount(2, $batch->products);
            $this->assertCount(1, $batch->statusHistories);
            $this->assertSame('menunggu-pemesanan', $batch->statusHistories->first()->newStatus->code);
            $this->assertSame(2, $batch->products->where('pivot.is_available', true)->count());
        }

        $openBatch = $batches->firstWhere('batch_number', 'OP-GO-CATALOG-OPEN');
        $closedBatch = $batches->firstWhere('batch_number', 'OP-GO-CATALOG-CLOSED');

        $this->assertTrue($openBatch->ordering_deadline->isFuture());
        $this->assertTrue($closedBatch->ordering_deadline->isPast());
        $this->assertEquals(125000, $openBatch->products->first()->pivot->dp_price);
        $this->assertEquals(250000, $openBatch->products->first()->pivot->full_price);
    }
}
