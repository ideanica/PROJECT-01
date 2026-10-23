<x-layouts.app title="Pembayaran {{ $order->order_code }}">
    <x-page-heading title="Tagihan & Pembayaran" description="Ringkasan tagihan dan riwayat pembayaran pesanan {{ $order->order_code }}.">
        <x-slot:action>
            <a class="admin-form-secondary" href="{{ route('admin.member-orders.show', $order, false) }}">Kembali ke pesanan</a>
        </x-slot:action>
    </x-page-heading>

    <p class="mb-4 text-sm text-zinc-600">{{ $order->member->display_name }} / {{ $order->batch->batch_number }}</p>
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,380px)]">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-zinc-50 p-4"><span class="text-xs text-zinc-500">Total pesanan</span><strong class="mt-1 block text-lg">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong></div>
                <div class="rounded-xl bg-zinc-50 p-4"><span class="text-xs text-zinc-500">Sudah terbayar</span><strong class="mt-1 block text-lg">Rp {{ number_format($order->paid_amount, 0, ',', '.') }}</strong></div>
                <div class="rounded-xl bg-zinc-50 p-4"><span class="text-xs text-zinc-500">Sisa</span><strong class="mt-1 block text-lg">Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}</strong></div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($order->payments->sortByDesc('paid_at') as $payment)
                    <article class="rounded-xl border border-zinc-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>{{ $payment->type_label }} · {{ $payment->type === 'refund' ? '−' : '' }}Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                                <p class="mt-1 text-xs text-zinc-500">{{ $payment->paid_at?->format('d M Y H:i') ?? 'Belum diverifikasi' }} · {{ match($payment->status) { 'verified' => 'Terverifikasi', 'pending' => 'Menunggu verifikasi', 'rejected' => 'Ditolak', default => $payment->status } }}</p>
                                @if($payment->notes)<p class="mt-2 text-sm text-zinc-600">{{ $payment->notes }}</p>@endif
                            </div>
                            <div class="flex gap-2">
                                @if($payment->proof_path)<a class="admin-form-secondary" href="{{ route('admin.member-orders.payments.proof', [$order, $payment], false) }}" target="_blank" rel="noopener">Bukti</a>@endif
                                @if(!$payment->customer_submitted)<form method="POST" action="{{ route('admin.member-orders.payments.destroy', [$order, $payment], false) }}" onsubmit="return confirm('Hapus record pembayaran ini?')">@csrf @method('DELETE')<button class="admin-form-danger-action" type="submit">Hapus</button></form>@else<a class="admin-form-secondary" href="{{ route('admin.transactions.show', $payment) }}">Periksa transaksi</a>@endif
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-zinc-300 p-5 text-sm text-zinc-500">Belum ada pembayaran tercatat.</p>
                @endforelse
            </div>
        </section>

        @if($order->has_outstanding_order_payment)
            <details class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm" @if($errors->any() || old('amount') !== null) open @endif>
                <summary class="cursor-pointer font-bold">Kirim bukti pembayaran di luar transaksi customer</summary>
                <p class="mt-1 text-sm text-zinc-500">Nominal dan jenis pembayaran mengikuti tagihan aktif. Bukti akan masuk ke menu Transaksi dan belum mengurangi tagihan sampai diverifikasi admin.</p>
                <form class="mt-5 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('admin.member-orders.payments.store', $order, false) }}">
                    @csrf
                    <div class="admin-form-readonly"><span>Jenis tagihan</span><strong>{{ $order->next_payment['label'] }}</strong><small>Jenis transaksi ditetapkan sistem.</small></div>
                    <x-text-input label="Nominal sesuai tagihan" name="amount" type="number" min="0.01" step="0.01" :value="old('amount', number_format($order->next_payment['amount'], 2, '.', ''))" required />
                    <label class="block"><span>Bukti pembayaran</span><input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
                    <label class="block"><span>Catatan internal</span><textarea name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea></label>
                    <button class="admin-form-primary" type="submit">Kirim untuk verifikasi</button>
                </form>
            </details>
        @endif
        @if($refundableAmount > 0 && !$order->is_refunded)
            <details class="mt-5 rounded-2xl border border-orange-200 bg-white p-5 shadow-sm" @if($errors->has('amount') || $errors->has('proof')) open @endif>
                <summary class="cursor-pointer font-bold text-orange-800">Catat pengembalian dana</summary>
                <p class="mt-2 text-sm text-zinc-600">Kirim nominal penuh yang sudah diterima dan belum direfund. Refund akan menutup progres pesanan setelah bukti transfer disimpan. Transaksi tertunda harus diperiksa terlebih dahulu.</p>
                <form class="mt-4 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('admin.member-orders.payments.refund', $order, false) }}">
                    @csrf
                    <x-text-input label="Nominal refund" name="amount" type="number" min="0.01" step="0.01" :value="number_format($refundableAmount, 2, '.', '')" required />
                    <label class="block"><span>Bukti transfer refund</span><input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
                    <label class="block"><span>Catatan internal</span><textarea name="notes" rows="2" maxlength="1000">{{ old('notes') }}</textarea></label>
                    <button class="admin-form-danger-action" type="submit">Catat refund dan tutup pesanan</button>
                </form>
            </details>
        @endif
    </div>
</x-layouts.app>
