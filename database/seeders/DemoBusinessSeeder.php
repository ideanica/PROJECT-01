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

        $customer = User::query()->where('email', config('seeders.demo_customer_email'))->firstOrFail();
        $member = Member::query()->whereKey($customer->member_id)->firstOrFail();
        $admin = User::query()->where('role', User::ROLE_ADMIN)->orderBy('id')->first();

        DB::transaction(function () use ($member, $admin): void {
            $group = CustomerGroup::query()->firstOrCreate(
                ['name' => '[DEMO] OceanPaws Community'],
                ['description' => 'Group demonstrasi untuk menguji pengelolaan pelanggan. '.self::MARKER, 'color' => '#e78eac', 'is_active' => true],
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

        $this->command?->info('Skenario demo bisnis siap: katalog, pelanggan, pesanan, histori status, tagihan, transaksi, dan pengiriman.');
    }

    private function seedPaymentSettings(): void
    {
        PaymentSetting::query()->findOrFail(1)->update(['dp_percentage' => 40]);
    }

    private function seedPaymentMethod(): PaymentMethod
    {
        $method = PaymentMethod::query()->firstOrCreate(
            ['name' => '[DEMO] QRIS OceanPaws'],
            [
                'type' => 'qris',
                'image_path' => null,
                'account_name' => 'OceanPaws Demo',
                'instructions' => 'Metode contoh untuk pengujian admin. Admin perlu memasang QRIS asli sebelum menerima pembayaran. '.self::MARKER,
                'is_active' => true,
            ],
        );

        if ($method->type !== 'qris' || ! str_contains((string) $method->instructions, self::MARKER)) {
            throw new RuntimeException('Data metode pembayaran demo sudah dipakai oleh sumber lain.');
        }

        $method->update([
            'is_active' => true,
            'instructions' => 'Metode contoh untuk pengujian admin. Admin perlu memasang QRIS asli sebelum menerima pembayaran. '.self::MARKER,
        ]);

        return $method;
    }

    /** @return array<string, Product> */
    private function seedProducts(): array
    {
        $definitions = [
            'lightstick' => ['[DEMO] Ocean Lightstick', 'Versi 2026', 250000],
            'photocard' => ['[DEMO] Ocean Photocard Set', 'Set isi 5', 75000],
            'plush' => ['[DEMO] Ocean Paws Plush', 'M size', 150000],
        ];
        $products = [];

        foreach ($definitions as $key => [$name, $variant, $price]) {
            $product = Product::query()->firstOrCreate(
                ['name' => $name, 'variant' => $variant],
                ['description' => 'Produk katalog contoh. Bukan barang atau transaksi nyata. '.self::MARKER, 'default_price' => $price, 'is_active' => true],
            );
            if (! str_contains((string) $product->description, self::MARKER)) {
                throw new RuntimeException("Produk {$name} sudah ada dan bukan milik seeder demo.");
            }
            $product->update(['default_price' => $price, 'is_active' => true]);
            $products[$key] = $product;
        }

        return $products;
    }

    /** @param array<string, Product> $products
     * @return array<string, Batch>
     */
    private function seedBatches(array $products, PaymentMethod $paymentMethod, ?User $admin): array
    {
        $waiting = OrderStatus::query()->where('scope', 'batch')->where('code', 'menunggu-pemesanan')->firstOrFail();
        $definitions = [
            'catalog' => ['OP-DEMO-CATALOG-OPEN', '[DEMO] Katalog Terbuka', 'menunggu-pemesanan', $products['lightstick'], 250000, now()->addDays(30), false],
            'paid' => ['OP-DEMO-PAID', '[DEMO] Batch Pesanan Lunas', 'arrived-admin', $products['plush'], 150000, now()->subDays(12), false],
            'unpaid' => ['OP-DEMO-UNPAID', '[DEMO] Batch Tagihan Belum Dibayar', 'sudah-dipesan', $products['photocard'], 75000, now()->subDays(6), false],
            'dp' => ['OP-DEMO-DP', '[DEMO] Batch Pembayaran DP', 'flight-to-indonesia', $products['lightstick'], 300000, now()->subDays(20), false],
            'pending' => ['OP-DEMO-PENDING', '[DEMO] Batch Menunggu Verifikasi', 'sudah-dipesan', $products['photocard'], 75000, now()->subDays(4), false],
            'history' => ['OP-DEMO-HISTORY', '[DEMO] Batch Riwayat Selesai', 'selesai', $products['plush'], 150000, now()->subDays(60), true],
        ];
        $batches = [];
        $transition = app(StatusTransitionService::class);

        foreach ($definitions as $key => [$number, $name, $statusCode, $product, $price, $deadline, $archived]) {
            $status = OrderStatus::query()->where('code', $statusCode)->firstOrFail();
            $batch = Batch::query()->firstOrNew(['batch_number' => $number]);
            $isNew = ! $batch->exists;
            if (! $isNew && ! str_contains((string) $batch->notes, self::MARKER)) {
                throw new RuntimeException("Nomor batch {$number} sudah dipakai data non-demo.");
            }
            $batch->fill([
                'batch_name' => $name,
                'description' => 'Batch contoh untuk menguji katalog dan alur admin. Tidak mewakili pembelian dari supplier. '.self::MARKER,
                'notes' => self::MARKER,
                'current_status_id' => $isNew ? $waiting->id : $batch->current_status_id,
                'payment_method_id' => $paymentMethod->id,
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
                    'note' => 'Status awal batch contoh. '.self::MARKER,
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
                $transition->transition($batch, $status, $admin, 'Status demo diperbarui oleh seeder. '.self::MARKER);
            } elseif (! $batch->statusHistories()->exists()) {
                $batch->statusHistories()->create([
                    'old_status_id' => null,
                    'new_status_id' => $status->id,
                    'note' => 'Status awal batch contoh. '.self::MARKER,
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
            'paid' => ['OP-DEMO-ORDER-PAID', 'paid', 'full', 150000, $products['plush'], 1, 150000, null],
            'unpaid' => ['OP-DEMO-ORDER-UNPAID', 'unpaid', 'full', 75000, $products['photocard'], 1, 75000, 'unpaid'],
            'dp' => ['OP-DEMO-ORDER-DP', 'dp', 'dp', 300000, $products['lightstick'], 1, 300000, null],
            'pending' => ['OP-DEMO-ORDER-PENDING', 'pending', 'full', 75000, $products['photocard'], 1, 75000, null],
            'history' => ['OP-DEMO-ORDER-HISTORY', 'history', 'full', 150000, $products['plush'], 2, 300000, 'paid'],
        ];
        $orders = [];
        $completeStatus = OrderStatus::query()->where('scope', 'all')->where('code', 'selesai')->firstOrFail();
        $transition = app(StatusTransitionService::class);

        foreach ($definitions as $key => [$code, $batchKey, $paymentType, $unitPrice, $product, $quantity, $total, $emsState]) {
            $batch = $batches[$batchKey];
            $order = MemberOrder::query()->firstOrNew(['order_code' => $code]);
            if ($order->exists && ! str_contains((string) $order->notes, self::MARKER)) {
                throw new RuntimeException("Kode pesanan {$code} sudah dipakai data non-demo.");
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
                'notes' => 'Pesanan demo yang terhubung dengan member, batch, dan produk. '.self::MARKER,
                'shipping_address' => $key === 'history' ? 'Alamat demonstrasi, tidak digunakan untuk pengiriman.' : null,
            ])->save();

            OrderItem::query()->updateOrCreate(
                ['member_order_id' => $order->id, 'product_id' => $product->id],
                [
                    'item_name' => $product->name,
                    'variant' => $product->variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $total,
                    'notes' => self::MARKER,
                ],
            );

            if ($key === 'history' && $order->override_status_id !== $completeStatus->id) {
                $transition->transition($order, $completeStatus, $admin, 'Pesanan contoh selesai untuk menguji histori. '.self::MARKER);
            } elseif ($key === 'history' && ! $order->statusHistories()->exists()) {
                $order->statusHistories()->create([
                    'old_status_id' => null,
                    'new_status_id' => $completeStatus->id,
                    'note' => 'Pesanan contoh selesai. '.self::MARKER,
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
            ['batch_id' => $batch->id, 'name' => '[DEMO] Pengiriman Riwayat'],
            [
                'method' => 'ems',
                'tracking_number' => 'DEMO-TRACKING-NO-REAL-SHIPMENT',
                'shipment_code' => 'DEMO-EMS-001',
                'current_status_id' => $status->id,
                'last_co_at' => now()->subDays(20)->toDateString(),
                'notes' => 'Data pengiriman contoh, tidak mewakili paket nyata. '.self::MARKER,
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
            $marker = self::MARKER.' '.$orderKey.' '.$type;
            $payment = OrderPayment::query()->firstOrCreate(
                ['member_order_id' => $order->id, 'type' => $type, 'notes' => $marker],
                [
                    'amount' => $amount,
                    'status' => $status,
                    'proof_path' => null,
                    'paid_at' => now()->subDays($orderKey === 'history' ? 35 : 2),
                    'created_by' => $admin?->id,
                    'customer_submitted' => false,
                    'reviewed_by' => in_array($status, ['verified', 'rejected'], true) ? $admin?->id : null,
                    'reviewed_at' => in_array($status, ['verified', 'rejected'], true) ? now()->subDay() : null,
                    'review_notes' => $status === 'rejected' ? 'Contoh penolakan seeder untuk memeriksa daftar transaksi.' : null,
                ],
            );

            if ($payment->notes !== $marker) {
                throw new RuntimeException("Pembayaran demo {$marker} sudah ada dan bukan milik seeder.");
            }
            $payment->update(['amount' => $amount, 'status' => $status, 'reviewed_by' => in_array($status, ['verified', 'rejected'], true) ? $admin?->id : null]);
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
