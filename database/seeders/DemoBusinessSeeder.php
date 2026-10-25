<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\ShipmentGroup;
use App\Models\StatusHistory;
use App\Models\User;
use App\Services\PaymentVerificationService;
use App\Services\StatusTransitionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoBusinessSeeder extends Seeder
{
    private const MARKER = '[DEMO:DemoBusinessSeeder]';

    public function run(): void
    {
        $this->call([
            OrderStatusSeeder::class,
            AdminUserSeeder::class,
            CustomerDemoAccountSeeder::class,
        ]);

        DB::transaction(fn () => $this->normalizeLegacyScenarioLabels());

        $customer = User::query()->where('email', config('seeders.demo_customer_email'))->firstOrFail();
        $member = Member::query()->whereKey($customer->member_id)->firstOrFail();
        $admin = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first();

        DB::transaction(function () use ($member, $admin): void {
            $group = CustomerGroup::query()->firstOrCreate(
                ['name' => 'Pelanggan Reguler'],
                ['description' => 'Pelanggan OceanPaws.', 'color' => '#e78eac', 'is_active' => true],
            );
            $member->update(['customer_group_id' => $group->id]);

            $this->seedPaymentSettings();
            $paymentMethod = $this->seedPaymentMethod();
            $products = $this->seedProducts();
            $scenarios = $this->seedBatches($products, $paymentMethod, $admin);
            $orders = $this->seedOrders($member, $products, $scenarios, $admin);
            $this->seedShipmentHistory($scenarios['history'], $orders['history']);
            $this->seedPayments($orders, $admin);
        });

        $this->command?->info('Data bisnis siap: katalog, pelanggan, pesanan, histori status, tagihan, transaksi, dan pengiriman.');
    }

    private function normalizeLegacyScenarioLabels(): void
    {
        $batchNumbers = [
            'OP-DEMO-CATALOG-OPEN' => 'OP-GO-2026-OPEN',
            'OP-DEMO-PAID' => 'OP-GO-2026-ARRIVED',
            'OP-DEMO-UNPAID' => 'OP-GO-2026-PHOTO',
            'OP-DEMO-DP' => 'OP-GO-2026-LIGHTSTICK',
            'OP-DEMO-PENDING' => 'OP-GO-2026-SPRING',
            'OP-DEMO-HISTORY' => 'OP-GO-2025-PLUSH',
        ];
        $batchNames = [
            'OP-GO-2026-OPEN' => 'Official Lightstick GO 2026',
            'OP-GO-2026-ARRIVED' => 'OceanPaws Plush GO 2026',
            'OP-GO-2026-PHOTO' => 'Photocard Set GO 2026',
            'OP-GO-2026-LIGHTSTICK' => 'Official Lightstick Special GO',
            'OP-GO-2026-SPRING' => 'Photocard Set Spring GO 2026',
            'OP-GO-2025-PLUSH' => 'OceanPaws Plush GO 2025',
        ];
        foreach ($batchNumbers as $old => $new) {
            $batch = Batch::query()->where('batch_number', $old)->first();
            if ($batch && str_contains((string) $batch->notes, self::MARKER)) {
                $batch->update([
                    'batch_number' => $new,
                    'batch_name' => $batchNames[$new],
                    'description' => 'Katalog merchandise yang tersedia untuk pemesanan.',
                    'notes' => null,
                ]);
            }
        }

        foreach (range(1, 6) as $sequence) {
            $legacyNumber = sprintf('OP-DEMO-%03d', $sequence);
            $batch = Batch::query()->where('batch_number', $legacyNumber)->first();
            if (! $batch) {
                continue;
            }

            $sourceUrl = null;
            if (preg_match('~https?://\S+~', (string) $batch->notes, $matches)) {
                $sourceUrl = rtrim($matches[0], '.');
            }
            $batch->update([
                'batch_number' => sprintf('OP-GO-2026-%03d', $sequence),
                'notes' => $sourceUrl ? 'Sumber gambar: '.$sourceUrl : null,
                'description' => str_replace(['Batch contoh', 'Batch merchandise arsip untuk contoh'], ['Pemesanan', 'Batch merchandise'], (string) $batch->description),
                'qris_image_path' => null,
            ]);
        }

        $orderCodes = [
            'OP-DEMO-ORDER-PAID' => 'OP-2026-1001',
            'OP-DEMO-ORDER-UNPAID' => 'OP-2026-1002',
            'OP-DEMO-ORDER-DP' => 'OP-2026-1003',
            'OP-DEMO-ORDER-PENDING' => 'OP-2026-1004',
            'OP-DEMO-ORDER-HISTORY' => 'OP-2025-0981',
        ];
        foreach ($orderCodes as $old => $new) {
            $order = MemberOrder::query()->where('order_code', $old)->first();
            if ($order && str_contains((string) $order->notes, self::MARKER)) {
                $order->update(['order_code' => $new, 'notes' => 'Pemesanan melalui katalog batch.', 'shipping_address' => null]);
            }
        }
        foreach (range(1, 6) as $sequence) {
            MemberOrder::query()->where('order_code', sprintf('ORD-DEMO-%03d', $sequence))
                ->update(['order_code' => sprintf('OP-2026-%04d', 2000 + $sequence)]);
        }

        $legacyProducts = [
            ['[DEMO] Ocean Lightstick', 'Versi 2026', 'Official Lightstick', 'Versi 2026', 'Lightstick resmi untuk koleksi penggemar.'],
            ['[DEMO] Ocean Photocard Set', 'Set isi 5', 'Photocard Set', 'Set isi 5', 'Set photocard isi lima lembar.'],
            ['[DEMO] Ocean Paws Plush', 'M size', 'OceanPaws Plush', 'Ukuran M', 'Boneka OceanPaws ukuran M.'],
            ['[DEMO] Ocean Lightstick', 'Demo version', 'Official Lightstick', 'Versi 2026', 'Lightstick resmi untuk koleksi penggemar.'],
            ['[DEMO] Ocean Photocard Set', 'Demo edition', 'Photocard Set', 'Set isi 5', 'Set photocard isi lima lembar.'],
        ];
        foreach ($legacyProducts as [$oldName, $oldVariant, $name, $variant, $description]) {
            $product = Product::query()->where('name', $oldName)->where('variant', $oldVariant)->first();
            if ($product && str_contains((string) $product->description, self::MARKER)) {
                $product->update(['name' => $name, 'variant' => $variant, 'description' => $description]);
            }
        }

        CustomerGroup::query()->where('name', '[DEMO] OceanPaws Community')->update(['name' => 'Pelanggan Reguler', 'description' => 'Pelanggan OceanPaws.']);
        $legacyPaymentMethod = PaymentMethod::query()->where('name', '[DEMO] QRIS OceanPaws')->where('instructions', 'like', '%'.self::MARKER.'%')->first();
        if ($legacyPaymentMethod) {
            Batch::query()->where('payment_method_id', $legacyPaymentMethod->id)->update(['payment_method_id' => null]);
            $legacyPaymentMethod->delete();
        }
        ShipmentGroup::query()->where('name', '[DEMO] Pengiriman Riwayat')->where('notes', 'like', '%'.self::MARKER.'%')->update([
            'name' => 'Serah Terima Pesanan', 'method' => 'other', 'tracking_number' => null,
            'shipment_code' => null, 'notes' => null,
        ]);
        OrderPayment::query()->where('notes', 'like', '%'.self::MARKER.'%')->update(['notes' => null]);
        OrderItem::query()->where('notes', self::MARKER)->update(['notes' => null]);
        StatusHistory::query()->where('note', 'like', '%'.self::MARKER.'%')->update(['note' => 'Status pesanan diperbarui.']);
    }

    private function seedPaymentSettings(): void
    {
        PaymentSetting::query()->findOrFail(1)->update(['dp_percentage' => 40]);
    }

    private function seedPaymentMethod(): ?PaymentMethod
    {
        return PaymentMethod::getActiveQris();
    }

    /** @return array<string, Product> */
    private function seedProducts(): array
    {
        $definitions = [
            'lightstick' => ['Official Lightstick', 'Versi 2026', 250000],
            'photocard' => ['Photocard Set', 'Set isi 5', 75000],
            'plush' => ['OceanPaws Plush', 'Ukuran M', 150000],
        ];
        $products = [];

        foreach ($definitions as $key => [$name, $variant, $price]) {
            $product = Product::query()->firstOrCreate(
                ['name' => $name, 'variant' => $variant],
                ['description' => $key === 'lightstick' ? 'Lightstick resmi untuk koleksi penggemar.' : ($key === 'photocard' ? 'Set photocard isi lima lembar.' : 'Boneka OceanPaws ukuran M.'), 'default_price' => $price, 'is_active' => true],
            );
            if (! in_array($product->description, ['Lightstick resmi untuk koleksi penggemar.', 'Set photocard isi lima lembar.', 'Boneka OceanPaws ukuran M.'], true)) {
                throw new RuntimeException("Produk {$name} sudah ada dengan sumber data lain.");
            }
            $product->update(['default_price' => $price, 'is_active' => true]);
            $products[$key] = $product;
        }

        return $products;
    }

    /** @param array<string, Product> $products
     * @return array<string, Batch>
     */
    private function seedBatches(array $products, ?PaymentMethod $paymentMethod, ?User $admin): array
    {
        $waiting = OrderStatus::query()->where('scope', 'batch')->where('code', 'menunggu-pemesanan')->firstOrFail();
        $definitions = [
            'catalog' => ['OP-GO-2026-OPEN', 'Official Lightstick GO 2026', 'menunggu-pemesanan', $products['lightstick'], 250000, now()->addDays(30), false],
            'paid' => ['OP-GO-2026-ARRIVED', 'OceanPaws Plush GO 2026', 'arrived-admin', $products['plush'], 150000, now()->subDays(12), false],
            'unpaid' => ['OP-GO-2026-PHOTO', 'Photocard Set GO 2026', 'sudah-dipesan', $products['photocard'], 75000, now()->subDays(6), false],
            'dp' => ['OP-GO-2026-LIGHTSTICK', 'Official Lightstick Special GO', 'flight-to-indonesia', $products['lightstick'], 300000, now()->subDays(20), false],
            'pending' => ['OP-GO-2026-SPRING', 'Photocard Set Spring GO 2026', 'sudah-dipesan', $products['photocard'], 75000, now()->subDays(4), false],
            'history' => ['OP-GO-2025-PLUSH', 'OceanPaws Plush GO 2025', 'selesai', $products['plush'], 150000, now()->subDays(60), true],
        ];
        $batches = [];
        $transition = app(StatusTransitionService::class);

        foreach ($definitions as $key => [$number, $name, $statusCode, $product, $price, $deadline, $archived]) {
            $status = OrderStatus::query()->where('code', $statusCode)->firstOrFail();
            $batch = Batch::query()->firstOrNew(['batch_number' => $number]);
            $isNew = ! $batch->exists;
            if (! $isNew && $batch->batch_name !== $name) {
                throw new RuntimeException("Nomor batch {$number} sudah dipakai data lain.");
            }
            $batch->fill([
                'batch_name' => $name,
                'description' => 'Katalog merchandise yang tersedia untuk pemesanan.',
                'notes' => null,
                'current_status_id' => $isNew ? $waiting->id : $batch->current_status_id,
                'payment_method_id' => $paymentMethod?->id,
                'ordering_deadline' => $deadline,
                'is_catalog_visible' => true,
                'is_archived' => $archived,
                'started_at' => $archived ? now()->subDays(58) : null,
                'completed_at' => $archived ? now()->subDays(2) : null,
            ])->save();

            if ($isNew) {
                $batch->statusHistories()->create([
                    'old_status_id' => null,
                    'new_status_id' => $waiting->id,
                    'note' => 'Batch dibuka untuk pemesanan.',
                    'changed_by' => $admin?->id,
                ]);
            }

            $batch->products()->sync([
                $product->id => [
                    'dp_price' => round($price * 0.4),
                    'full_price' => $price,
                    'sort_order' => 1,
                    'is_available' => true,
                ],
            ]);

            if ($batch->current_status_id !== $status->id) {
                $transition->transition($batch, $status, $admin, 'Status batch diperbarui.');
            } elseif (! $batch->statusHistories()->exists()) {
                $batch->statusHistories()->create([
                    'old_status_id' => null,
                    'new_status_id' => $status->id,
                    'note' => 'Status awal batch.',
                    'changed_by' => $admin?->id,
                ]);
            }

            $batches[$key] = $batch->fresh();
        }

        return $batches;
    }

    /** @param array<string, Batch> $batches
     * @return array<string, MemberOrder>
     */
    private function seedOrders(Member $member, array $products, array $batches, ?User $admin): array
    {
        $definitions = [
            'paid' => ['OP-2026-1001', 'paid', 'full', 150000, $products['plush'], 1, 150000, null],
            'unpaid' => ['OP-2026-1002', 'unpaid', 'full', 75000, $products['photocard'], 1, 75000, 'unpaid'],
            'dp' => ['OP-2026-1003', 'dp', 'dp', 300000, $products['lightstick'], 1, 300000, null],
            'pending' => ['OP-2026-1004', 'pending', 'full', 75000, $products['photocard'], 1, 75000, null],
            'history' => ['OP-2025-0981', 'history', 'full', 150000, $products['plush'], 2, 300000, 'paid'],
        ];
        $orders = [];
        $completeStatus = OrderStatus::query()->where('scope', 'all')->where('code', 'selesai')->firstOrFail();
        $transition = app(StatusTransitionService::class);

        foreach ($definitions as $key => [$code, $batchKey, $paymentType, $unitPrice, $product, $quantity, $total, $emsState]) {
            $batch = $batches[$batchKey];
            $order = MemberOrder::query()->firstOrNew(['order_code' => $code]);
            if ($order->exists && ($order->member_id !== $member->id || $order->batch_id !== $batch->id)) {
                throw new RuntimeException("Kode pesanan {$code} sudah dipakai data lain.");
            }
            $order->fill([
                'member_id' => $member->id,
                'batch_id' => $batch->id,
                'order_source' => 'catalog',
                'payment_type' => $paymentType,
                'dp_percentage' => $paymentType === 'dp' ? 40 : null,
                'total_amount' => $total,
                'ems_tax_amount' => match ($key) {
                    'paid', 'history' => 25000, 'unpaid' => 18000, default => null
                },
                'ems_tax_status' => match ($key) {
                    'paid', 'history' => 'paid', 'unpaid' => 'unpaid', default => 'not_billed'
                },
                'ems_tax_due_date' => in_array($key, ['paid', 'history'], true) ? now()->subDays(15)->toDateString() : ($key === 'unpaid' ? now()->addDays(7)->toDateString() : null),
                'notes' => 'Pemesanan melalui katalog batch.',
                'shipping_address' => null,
            ])->save();

            OrderItem::query()->updateOrCreate(
                ['member_order_id' => $order->id, 'product_id' => $product->id],
                [
                    'item_name' => $product->name,
                    'variant' => $product->variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $total,
                    'notes' => null,
                ],
            );

            if ($key === 'history' && $order->override_status_id !== $completeStatus->id) {
                $transition->transition($order, $completeStatus, $admin, 'Pesanan selesai diproses.');
            } elseif ($key === 'history' && ! $order->statusHistories()->exists()) {
                $order->statusHistories()->create([
                    'old_status_id' => null,
                    'new_status_id' => $completeStatus->id,
                    'note' => 'Pesanan selesai diproses.',
                    'changed_by' => $admin?->id,
                ]);
            }

            $orders[$key] = $order->fresh();
        }

        return $orders;
    }

    /** @param array<string, Batch> $batches
     * @param  array<string, MemberOrder>  $orders
     */
    private function seedShipmentHistory(Batch $batch, MemberOrder $order): void
    {
        $status = OrderStatus::query()->where('scope', 'all')->where('code', 'selesai')->firstOrFail();
        $shipment = ShipmentGroup::query()->firstOrCreate(
            ['batch_id' => $batch->id, 'name' => 'Serah Terima Pesanan'],
            [
                'method' => 'other',
                'tracking_number' => null,
                'shipment_code' => null,
                'current_status_id' => $status->id,
                'last_co_at' => now()->subDays(20)->toDateString(),
                'notes' => null,
            ],
        );
        OrderItem::query()->where('member_order_id', $order->id)
            ->update(['shipment_group_id' => $shipment->id]);
    }

    /** @param array<string, MemberOrder> $orders */
    private function seedPayments(array $orders, ?User $admin): void
    {
        $definitions = [
            ['paid', 'full_payment', 150000, 'verified'],
            ['history', 'full_payment', 300000, 'verified'],
            ['dp', 'dp', 120000, 'verified'],
            ['pending', 'full_payment', 75000, 'pending'],
            ['unpaid', 'dp', 30000, 'rejected'],
            ['paid', 'ems', 25000, 'verified'],
            ['history', 'ems', 25000, 'verified'],
        ];

        foreach ($definitions as [$orderKey, $type, $amount, $status]) {
            $order = $orders[$orderKey];
            $payment = OrderPayment::query()->firstOrNew([
                'member_order_id' => $order->id,
                'type' => $type,
                'customer_submitted' => false,
                'created_by' => $admin?->id,
            ]);
            if (! $payment->exists) {
                $payment->fill([
                    'amount' => $amount,
                    'status' => $status,
                    'proof_path' => null,
                    'paid_at' => now()->subDays($orderKey === 'history' ? 35 : 2),
                    'created_by' => $admin?->id,
                    'customer_submitted' => false,
                    'reviewed_by' => in_array($status, ['verified', 'rejected'], true) ? $admin?->id : null,
                    'reviewed_at' => in_array($status, ['verified', 'rejected'], true) ? now()->subDay() : null,
                    'review_notes' => $status === 'rejected' ? 'Pembayaran tidak sesuai dengan nilai tagihan.' : null,
                    'notes' => null,
                ])->save();
            } else {
                $payment->update([
                    'amount' => $amount, 'status' => $status,
                    'reviewed_by' => in_array($status, ['verified', 'rejected'], true) ? $admin?->id : null,
                ]);
            }
        }

        $verification = app(PaymentVerificationService::class);
        foreach ($orders as $key => $order) {
            $verification->sync($order);
            if (in_array($key, ['paid', 'history'], true)) {
                $verification->sync($order, ems: true);
            }
        }
    }
}
