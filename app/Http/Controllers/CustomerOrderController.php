<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerOrderRequest;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Services\BatchEmsTaxService;
use App\Services\OrderCodeGenerator;
use App\Services\PaymentSchedule;
use App\Services\PaymentVerificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function store(
        StoreCustomerOrderRequest $request,
        Batch $batch,
        PaymentSchedule $schedule,
        PaymentVerificationService $payments,
        BatchEmsTaxService $emsTaxes,
        OrderCodeGenerator $orderCodes,
    ) {
        $data = $request->validated();
        $memberOrder = DB::transaction(function () use ($request, $batch, $data, $schedule, $payments, $emsTaxes, $orderCodes): MemberOrder {
            $batch = Batch::query()->lockForUpdate()->findOrFail($batch->id);
            if (! $batch->catalog_is_open || $batch->orders_locked) {
                throw ValidationException::withMessages(['batch' => 'Pemesanan batch ini sudah ditutup.']);
            }
            if (! $batch->has_payment_method) {
                throw ValidationException::withMessages(['batch' => 'Metode pembayaran batch belum tersedia. Hubungi admin sebelum memesan.']);
            }
            if ($batch->ems_tax_amount !== null && $batch->orders()
                ->whereHas('payments', fn ($payments) => $payments->where('type', 'ems')->whereIn('status', ['pending', 'verified']))
                ->exists()) {
                throw ValidationException::withMessages(['batch' => 'Pemesanan baru ditutup setelah bukti atau pembayaran EMS diproses agar alokasi tagihan tidak berubah.']);
            }

            $member = Member::query()->lockForUpdate()->findOrFail($request->user()->member_id);
            if (! $member->isAvailableForOrder()) {
                throw ValidationException::withMessages(['account' => 'Akun member belum aktif atau belum disetujui untuk membuat pesanan.']);
            }

            $existingOrder = MemberOrder::query()
                ->where('member_id', $member->id)
                ->where('batch_id', $batch->id)
                ->first();
            if ($existingOrder) {
                return $existingOrder;
            }

            $quantities = collect($data['items'])->keyBy(fn ($item) => (int) $item['product_id']);
            $products = $batch->products()
                ->whereIn('products.id', $quantities->keys())
                ->where('products.is_active', true)
                ->wherePivot('is_available', true)
                ->get()
                ->keyBy('id');
            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages(['items' => 'Salah satu produk sudah tidak tersedia. Muat ulang katalog dan pilih kembali.']);
            }

            $totalCents = 0;
            foreach ($quantities as $productId => $item) {
                $product = $products->get($productId);
                $unitPrice = (float) ($product->pivot->full_price ?? $product->default_price ?? 0);
                if ($unitPrice <= 0) {
                    throw ValidationException::withMessages(['items' => 'Harga salah satu produk belum tersedia. Hubungi admin.']);
                }
                $totalCents += (int) round($unitPrice * 100) * (int) $item['quantity'];
            }
            $total = number_format($totalCents / 100, 2, '.', '');

            $order = MemberOrder::query()->create([
                'order_code' => 'TMP-'.Str::uuid(),
                'member_id' => $member->id,
                'batch_id' => $batch->id,
                'order_source' => 'catalog',
                'payment_type' => $data['payment_type'],
                'dp_percentage' => $data['payment_type'] === 'dp' ? $schedule->percentage() : null,
                'total_amount' => $total,
            ]);
            $order->forceFill(['order_code' => $orderCodes->for($order)])->save();

            foreach ($quantities as $productId => $item) {
                $product = $products->get($productId);
                $quantity = (int) $item['quantity'];
                $unitPrice = (int) round((float) ($product->pivot->full_price ?? $product->default_price) * 100);
                $order->items()->create([
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'variant' => $product->variant,
                    'quantity' => $quantity,
                    'unit_price' => number_format($unitPrice / 100, 2, '.', ''),
                    'subtotal' => number_format(($unitPrice * $quantity) / 100, 2, '.', ''),
                ]);
            }

            $payments->sync($order);
            $emsTaxes->sync($batch);

            return $order;
        });

        if ($memberOrder->wasRecentlyCreated) {
            return redirect()->route('billing.payments.show', [$memberOrder, 'order'])
                ->with('status', 'Pesanan berhasil dibuat. Lanjutkan pembayaran sesuai nominal dari sistem.');
        }

        return redirect()->route('billing.payments.show', [$memberOrder, 'order'])
            ->with('status', 'Kamu sudah memiliki pesanan untuk batch ini. Lanjutkan dari tagihan yang tersedia.');
    }
}
