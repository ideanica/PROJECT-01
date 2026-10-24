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
        $this->call(OrderStatusSeeder::class);

        $waitingStatus = OrderStatus::query()
            ->where('code', 'menunggu-pemesanan')
            ->where('scope', 'batch')
            ->firstOrFail();

        DB::transaction(function () use ($waitingStatus): void {
            $products = collect([
                [
                    'name' => '[DEMO] Ocean Lightstick',
                    'variant' => 'Demo version',
                    'description' => 'Produk contoh yang dibuat oleh CustomerCatalogDemoSeeder untuk menguji alur katalog.',
                    'full_price' => 250000,
                    'dp_price' => 125000,
                ],
                [
                    'name' => '[DEMO] Ocean Photocard Set',
                    'variant' => 'Demo edition',
                    'description' => 'Produk contoh yang dibuat oleh CustomerCatalogDemoSeeder untuk menguji variasi dan harga.',
                    'full_price' => 75000,
                    'dp_price' => 37500,
                ],
            ])->map(fn (array $attributes): Product => $this->findOrCreateDemoProduct($attributes));

            $batches = [
                [
                    'batch_number' => 'OP-DEMO-CATALOG-OPEN',
                    'batch_name' => 'Demo Katalog Terbuka',
                    'ordering_deadline' => now()->addDays(30),
                ],
                [
                    'batch_number' => 'OP-DEMO-CATALOG-CLOSED',
                    'batch_name' => 'Demo Katalog Ditutup',
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
                        'dp_price' => $product->name === '[DEMO] Ocean Lightstick' ? 125000 : 37500,
                        'full_price' => $product->name === '[DEMO] Ocean Lightstick' ? 250000 : 75000,
                        'sort_order' => $index + 1,
                        'is_available' => true,
                    ]);
                }
            }
        });

        $this->command?->info('Katalog demo dibuat dengan produk dan status batch terkait. Tidak ada pesanan atau transaksi demo yang ditambahkan.');
    }

    private function findOrCreateDemoProduct(array $attributes): Product
    {
        $product = Product::query()
            ->where('name', $attributes['name'])
            ->where('variant', $attributes['variant'])
            ->first();

        if ($product) {
            if (! str_contains((string) $product->description, self::SOURCE_MARKER)) {
                throw new RuntimeException("Produk {$attributes['name']} sudah ada dan bukan milik seeder katalog demo.");
            }

            return $product;
        }

        return Product::query()->create([
            'name' => $attributes['name'],
            'variant' => $attributes['variant'],
            'default_price' => $attributes['full_price'],
            'is_active' => true,
            'description' => $attributes['description'].' '.self::SOURCE_MARKER,
        ]);
    }

    private function findOrCreateDemoBatch(array $attributes, OrderStatus $status): Batch
    {
        $batch = Batch::query()->where('batch_number', $attributes['batch_number'])->first();

        if ($batch) {
            if (! str_contains((string) $batch->notes, self::SOURCE_MARKER)) {
                throw new RuntimeException("Batch {$attributes['batch_number']} sudah ada dan bukan milik seeder katalog demo.");
            }

            return $batch;
        }

        $batch = Batch::query()->create([
            ...$attributes,
            'current_status_id' => $status->id,
            'description' => 'Data katalog demonstrasi untuk menguji daftar batch, detail produk, dan filter status. Tidak mewakili transaksi atau pemasok sungguhan.',
            'notes' => self::SOURCE_MARKER,
            'is_catalog_visible' => true,
            'is_archived' => false,
        ]);

        $batch->statusHistories()->create([
            'old_status_id' => null,
            'new_status_id' => $status->id,
            'note' => 'Status awal batch demo dari CustomerCatalogDemoSeeder.',
        ]);

        return $batch;
    }
}
