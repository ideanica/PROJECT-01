<x-layouts.app title="Pembayaran {{ $order->order_code }}">
    <x-page-heading title="Payment Ledger" description="Catat DP, cicilan, dan pelunasan sebagai transaksi terpisah agar histori nominal tetap auditable.">
        <x-slot:action>
            <a class="admin-form-secondary" href="{{ route('admin.member-orders.show', $order, false) }}">Kembali ke pesanan</a>
        </x-slot:action>
    </x-page-heading>

    <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-zinc-50 p-4"><span class="text-xs text-zinc-500">Total order</span><strong class="mt-1 block text-lg">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong></div>
                <div class="rounded-xl bg-zinc-50 p-4"><span class="text-xs text-zinc-500">Sudah terbayar</span><strong class="mt-1 block text-lg">Rp {{ number_format($order->paid_amount, 0, ',', '.') }}</strong></div>
                <div class="rounded-xl bg-zinc-50 p-4"><span class="text-xs text-zinc-500">Sisa</span><strong class="mt-1 block text-lg">Rp {{ number_format($order->remaining_amount, 0, ',', '.') }}</strong></div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($order->payments->sortByDesc('paid_at') as $payment)
                    <article class="rounded-xl border border-zinc-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <strong>{{ strtoupper($payment->type) }} · Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</strong>
                                <p class="mt-1 text-xs text-zinc-500">{{ $payment->paid_at?->format('d M Y H:i') ?? 'Belum diverifikasi' }} · {{ ucfirst($payment->status) }}</p>
                                @if($payment->notes)<p class="mt-2 text-sm text-zinc-600">{{ $payment->notes }}</p>@endif
                            </div>
                            <div class="flex gap-2">
                                @if($payment->proof_path)<a class="admin-form-secondary" href="{{ route('admin.member-orders.payments.proof', [$order, $payment], false) }}" target="_blank">Bukti</a>@endif
                                <form method="POST" action="{{ route('admin.member-orders.payments.destroy', [$order, $payment], false) }}" onsubmit="return confirm('Hapus record pembayaran ini?')">@csrf @method('DELETE')<button class="admin-form-danger-action" type="submit">Hapus</button></form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-zinc-300 p-5 text-sm text-zinc-500">Belum ada transaksi pembayaran pada ledger.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold">Tambah pembayaran</h2>
            <p class="mt-1 text-sm text-zinc-500">Status pembayaran order akan disinkronkan otomatis dari total transaksi verified.</p>
            <form class="mt-5 space-y-4" method="POST" enctype="multipart/form-data" action="{{ route('admin.member-orders.payments.store', $order, false) }}">
                @csrf
                <label class="block"><span>Tipe</span><select name="type" required><option value="dp">DP</option><option value="installment">Cicilan tambahan</option><option value="settlement">Pelunasan</option><option value="other">Lainnya</option></select></label>
                <x-text-input label="Nominal" name="amount" type="number" min="1" step="0.01" required />
                <label class="block"><span>Status</span><select name="status" required><option value="verified">Verified</option><option value="pending">Pending</option><option value="rejected">Rejected</option></select></label>
                <x-text-input label="Tanggal bayar" name="paid_at" type="datetime-local" />
                <label class="block"><span>Bukti pembayaran</span><input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf"></label>
                <label class="block"><span>Catatan</span><textarea name="notes" rows="3"></textarea></label>
                <button class="admin-form-primary" type="submit">Simpan pembayaran</button>
            </form>
        </section>
    </div>
</x-layouts.app>
