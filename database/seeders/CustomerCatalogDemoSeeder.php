<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\OrderStatus;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerCatalogDemoSeeder extends Seeder
{
    private const SOURCE_MARKER = '[seed:CustomerCatalogDemoSeeder]';

    public function run(): void
    {
        $this->call([OrderStatusSeeder::class, PreviewPaymentMethodSeeder::class]);

        $waitingStatus = OrderStatus::query()
            ->where('code', 'menunggu-pemesanan')
            ->where('scope', 'batch')
            ->firstOrFail();

        DB::transaction(function () use ($waitingStatus): void {
            $products = collect([
                [
                    'name' => 'Official Lightstick',
                    'variant' => 'Versi 2026',
                    'description' => 'Lightstick resmi untuk koleksi penggemar.',
                    'full_price' => 250000,
                    'dp_price' => 125000,
                ],
                [
                    'name' => 'Photocard Set',
                    'variant' => 'Set isi 5',
                    'description' => 'Set photocard isi lima lembar.',
                    'full_price' => 75000,
                    'dp_price' => 37500,
                ],
            ])->map(fn (array $attributes): Product => $this->findOrCreateDemoProduct($attributes));

            $batches = [
                [
                    'batch_number' => 'OP-GO-CATALOG-OPEN',
                    'batch_name' => 'Katalog Merchandise Juni 2026',
                    'ordering_deadline' => now()->addDays(30),
                ],
                [
                    'batch_number' => 'OP-GO-CATALOG-CLOSED',
                    'batch_name' => 'Katalog Merchandise Mei 2026',
                    'ordering_deadline' => now()->subDay(),
                ],
            ];

            foreach ($batches as $attributes) {
                $batch = $this->findOrCreateDemoBatch($attributes, $waitingStatus);

                foreach ($products as $index => $product) {
                    if ($batch->products()->whereKey($product->id)->exists()) {
                        continue;
                    }

                    $batch->products()->attach($product->id, [
                        'dp_price' => $product->name === 'Official Lightstick' ? 125000 : 37500,
                        'full_price' => $product->name === 'Official Lightstick' ? 250000 : 75000,
                        'sort_order' => $index + 1,
                        'is_available' => true,
                    ]);
                }
            }
        });

        $this->command?->info('Katalog dibuat dengan produk dan status batch terkait.');
    }

    private function findOrCreateDemoProduct(array $attributes): Product
    {
        $product = Product::query()
            ->where('name', $attributes['name'])
            ->where('variant', $attributes['variant'])
            ->first();

        if ($product) {
            if ($product->description !== $attributes['description']) {
                throw new RuntimeException("Produk {$attributes['name']} sudah ada dengan sumber data lain.");
            }

            return $product;
        }

        return Product::query()->create([
            'name' => $attributes['name'],
            'variant' => $attributes['variant'],
            'default_price' => $attributes['full_price'],
            'is_active' => true,
            'description' => $attributes['description'],
        ]);
    }

    private function findOrCreateDemoBatch(array $attributes, OrderStatus $status): Batch
    {
        $batch = Batch::query()->where('batch_number', $attributes['batch_number'])->first();
        $legacyNumbers = [
            'OP-GO-CATALOG-OPEN' => 'OP-DEMO-CATALOG-OPEN',
            'OP-GO-CATALOG-CLOSED' => 'OP-DEMO-CATALOG-CLOSED',
        ];
        if (! $batch && isset($legacyNumbers[$attributes['batch_number']])) {
            $legacy = Batch::query()->where('batch_number', $legacyNumbers[$attributes['batch_number']])->first();
            if ($legacy && str_contains((string) $legacy->notes, self::SOURCE_MARKER)) {
                $legacy->update([
                    'batch_number' => $attributes['batch_number'],
                    'batch_name' => $attributes['batch_name'],
                    'description' => 'Katalog merchandise yang tersedia untuk pemesanan.',
                    'notes' => null,
                ]);
                $batch = $legacy->fresh();
            }
        }

        if ($batch) {
            if ($batch->batch_name !== $attributes['batch_name']) {
                throw new RuntimeException("Batch {$attributes['batch_number']} sudah ada dengan sumber data lain.");
            }

            return $batch;
        }

        $batch = Batch::query()->create([
            ...$attributes,
            'current_status_id' => $status->id,
            'description' => 'Katalog merchandise yang tersedia untuk pemesanan.',
            'notes' => null,
            'is_catalog_visible' => true,
            'is_archived' => false,
        ]);

        $batch->statusHistories()->create([
            'old_status_id' => null,
            'new_status_id' => $status->id,
            'note' => 'Batch dibuka untuk pemesanan.',
        ]);

        return $batch;
    }
}
