<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PreviewPaymentMethodSeeder extends Seeder
{
    private const PREVIEW_IMAGE = 'assets/qr-placeholder.png';

    public function run(): void
    {
        if (PaymentMethod::getActiveQris()) {
            return;
        }

        $method = PaymentMethod::query()->firstOrNew([
            'name' => 'QRIS OceanPaws Preview',
            'type' => 'qris',
        ]);

        $method->fill([
            'image_path' => self::PREVIEW_IMAGE,
            'account_name' => 'OceanPaws Preview',
            'instructions' => 'Mode pratinjau: QR ini hanya gambar contoh dan tidak menerima pembayaran. Kirim bukti simulasi untuk menguji verifikasi transaksi oleh admin.',
            'is_active' => true,
        ])->save();
    }
}
