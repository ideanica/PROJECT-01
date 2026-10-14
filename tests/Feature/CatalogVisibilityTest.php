<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_uses_admin_visibility_variants_prices_and_deadline(): void
    {
        $batch = Batch::factory()->create(['batch_name' => 'Published Wish Batch', 'is_catalog_visible' => true, 'ordering_deadline' => now()->subDay()]);
        Batch::factory()->create(['batch_name' => 'Hidden Wish Batch', 'is_catalog_visible' => false]);
        Batch::factory()->create(['batch_name' => 'Archived Wish Batch', 'is_catalog_visible' => true, 'is_archived' => true]);
        $product = Product::factory()->create(['name' => 'Available Album']);
        $unavailable = Product::factory()->create(['name' => 'Unavailable Album']);
        $inactive = Product::factory()->create(['name' => 'Inactive Album', 'is_active' => false]);
        $batch->products()->attach($product, ['dp_price' => 100000, 'full_price' => 250000, 'is_available' => true]);
        $batch->products()->attach($unavailable, ['is_available' => false]);
        $batch->products()->attach($inactive, ['is_available' => true]);
        $this->get(route('catalog.index'))->assertOk()->assertSee('Published Wish Batch')
            ->assertSee('Available Album')->assertSee('100.000')->assertSee('250.000')->assertSee('Pemesanan ditutup')
            ->assertDontSee('Hidden Wish Batch')->assertDontSee('Archived Wish Batch')->assertDontSee('Unavailable Album')->assertDontSee('Inactive Album');
    }
}
