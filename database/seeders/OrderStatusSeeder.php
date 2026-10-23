<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = collect([
            ['name' => 'Menunggu Pemesanan ke Supplier', 'code' => 'menunggu-pemesanan', 'description' => 'Batch sudah dibuat dan menunggu proses pemesanan produk ke store atau supplier.', 'color' => '#64748b', 'sequence' => 10, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => true, 'is_final' => false],
            ['name' => 'Sudah Dipesan ke Supplier', 'code' => 'sudah-dipesan', 'description' => 'Seluruh produk dalam batch sudah berhasil dipesan ke store atau supplier.', 'color' => '#2563eb', 'sequence' => 20, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Tiba di Gudang Korea', 'code' => 'arrived-wh-korea', 'description' => 'Produk dalam batch sudah tiba di gudang Korea dan menunggu pengiriman ke Indonesia.', 'color' => '#7c3aed', 'sequence' => 30, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Tiba di Gudang China', 'code' => 'arrived-wh-china', 'description' => 'Produk dalam batch sudah tiba di gudang China dan menunggu pengiriman ke Indonesia.', 'color' => '#9333ea', 'sequence' => 31, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Tiba di Gudang Jepang', 'code' => 'arrived-wh-japan', 'description' => 'Produk dalam batch sudah tiba di gudang Jepang dan menunggu pengiriman ke Indonesia.', 'color' => '#a855f7', 'sequence' => 32, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Dikirim via Udara ke Indonesia', 'code' => 'flight-to-indonesia', 'description' => 'Batch sedang dikirim menuju Indonesia melalui jalur udara.', 'color' => '#0891b2', 'sequence' => 40, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Dikirim via Laut ke Indonesia', 'code' => 'sea-to-indonesia', 'description' => 'Batch sedang dikirim menuju Indonesia melalui jalur laut.', 'color' => '#0284c7', 'sequence' => 41, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Proses Bea Cukai', 'code' => 'customs-clearance', 'description' => 'Batch sedang menjalani pemeriksaan dan proses bea cukai di Indonesia.', 'color' => '#db2777', 'sequence' => 50, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Tiba di Gudang Admin', 'code' => 'arrived-admin', 'description' => 'Batch sudah tiba di gudang admin dan sedang diperiksa atau disortir.', 'color' => '#0d9488', 'sequence' => 60, 'status_type' => 'process', 'scope' => 'batch', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Siap Distribusi', 'code' => 'siap-distribusi', 'description' => 'Barang dalam batch atau pesanan sudah siap diambil atau dikirim kepada pelanggan.', 'color' => '#059669', 'sequence' => 70, 'status_type' => 'process', 'scope' => 'all', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Pesanan Dikirim ke Pelanggan', 'code' => 'dikirim-ke-customer', 'description' => 'Pesanan pelanggan ini sudah diserahkan kepada ekspedisi lokal.', 'color' => '#16a34a', 'sequence' => 80, 'status_type' => 'process', 'scope' => 'member_order', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Selesai', 'code' => 'selesai', 'description' => 'Barang sudah diterima pelanggan dan seluruh proses dinyatakan selesai.', 'color' => '#15803d', 'sequence' => 90, 'status_type' => 'success', 'scope' => 'all', 'is_initial' => false, 'is_final' => true],
            ['name' => 'Dibatalkan', 'code' => 'dibatalkan', 'description' => 'Pesanan dibatalkan dan tidak dilanjutkan.', 'color' => '#dc2626', 'sequence' => 99, 'status_type' => 'cancelled', 'scope' => 'all', 'is_initial' => false, 'is_final' => true],

            ['name' => 'Menunggu Pembayaran DP', 'code' => 'menunggu-dp', 'description' => 'Pelanggan belum menyelesaikan pembayaran uang muka atau DP.', 'color' => '#ca8a04', 'sequence' => 10, 'status_type' => 'process', 'scope' => 'payment', 'is_initial' => true, 'is_final' => false],
            ['name' => 'Menunggu Pelunasan', 'code' => 'menunggu-pelunasan', 'description' => 'Pembayaran DP sudah diterima dan masih menunggu pelunasan dari pelanggan.', 'color' => '#d97706', 'sequence' => 20, 'status_type' => 'process', 'scope' => 'payment', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Pembayaran Lunas', 'code' => 'lunas', 'description' => 'Pembayaran pelanggan telah diterima seluruhnya.', 'color' => '#059669', 'sequence' => 90, 'status_type' => 'success', 'scope' => 'payment', 'is_initial' => false, 'is_final' => true],
            ['name' => 'Dana Dikembalikan (Refund)', 'code' => 'refund', 'description' => 'Dana pembayaran pesanan sedang atau sudah dikembalikan kepada pelanggan.', 'color' => '#ea580c', 'sequence' => 95, 'status_type' => 'failed', 'scope' => 'payment', 'is_initial' => false, 'is_final' => true],
            ['name' => 'Selesai (Refund)', 'code' => 'refunded', 'description' => 'Pesanan selesai dan seluruh progress ditutup karena dana telah dikembalikan.', 'color' => '#c2410c', 'sequence' => 96, 'status_type' => 'failed', 'scope' => 'member_order', 'is_initial' => false, 'is_final' => true],
            ['name' => 'Pesanan Berhasil Diamankan', 'code' => 'secured', 'description' => 'Slot atau seluruh produk pada pesanan pelanggan ini sudah berhasil diamankan.', 'color' => '#4f46e5', 'sequence' => 21, 'status_type' => 'process', 'scope' => 'member_order', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Item Kurang', 'code' => 'barang-kurang', 'description' => 'Jumlah produk yang diterima untuk item ini belum lengkap.', 'color' => '#be123c', 'sequence' => 90, 'status_type' => 'failed', 'scope' => 'order_item', 'is_initial' => false, 'is_final' => false],
            ['name' => 'Item Rusak', 'code' => 'barang-rusak', 'description' => 'Item diterima dalam kondisi rusak dan memerlukan tindak lanjut.', 'color' => '#b91c1c', 'sequence' => 91, 'status_type' => 'failed', 'scope' => 'order_item', 'is_initial' => false, 'is_final' => false],
        ])->map(function (array $status): array {
            $status['locks_order_editing'] = in_array($status['code'], [
                'sudah-dipesan',
                'arrived-wh-korea',
                'arrived-wh-china',
                'arrived-wh-japan',
                'flight-to-indonesia',
                'sea-to-indonesia',
                'customs-clearance',
                'arrived-admin',
                'siap-distribusi',
                'selesai',
                'dibatalkan',
            ], true);

            return $status + ['is_active' => true];
        });

        $existingCodes = OrderStatus::query()->whereIn('code', $statuses->pluck('code'))->pluck('code');
        $missingStatuses = $statuses->reject(fn (array $status) => $existingCodes->contains($status['code']));
        if ($missingStatuses->isEmpty()) {
            return;
        }

        $timestamp = now();
        OrderStatus::query()->insert($missingStatuses->map(fn (array $status) => $status + [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all());
    }
}
