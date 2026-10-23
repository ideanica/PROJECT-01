<x-layouts.app title="Transaksi #{{ $payment->id }}">
    @php($formatMoney = fn ($amount) => 'Rp '.number_format((float) $amount, fmod((float) $amount, 1) > 0 ? 2 : 0, ',', '.'))
    <x-page-heading title="Detail transaksi #{{ $payment->id }}" description="Cocokkan nominal, tanggal, dan penerima pada bukti sebelum memverifikasi.">
        <x-slot:action><a class="admin-form-secondary" href="{{ route('admin.transactions.index') }}">Kembali ke transaksi</a></x-slot:action>
    </x-page-heading>
    <div class="transaction-review-grid">
        <section class="transaction-panel">
            <header class="flex flex-wrap items-center justify-between gap-3 mb-5"><h2 class="font-bold">Bukti pembayaran</h2>@if($payment->proof_path)<a class="admin-form-secondary" target="_blank" rel="noopener" href="{{ route('admin.member-orders.payments.proof', [$payment->order, $payment]) }}">Buka file asli</a>@endif</header>
            @if($payment->proof_path)
                @if(strtolower(pathinfo($payment->proof_path, PATHINFO_EXTENSION)) === 'pdf')
                    <div class="transaction-proof-placeholder"><strong>Bukti dalam format PDF</strong><p>Buka file asli untuk memeriksa dokumen pembayaran.</p></div>
                @else
                    <a href="{{ route('admin.member-orders.payments.proof', [$payment->order, $payment]) }}" target="_blank" rel="noopener"><img class="transaction-proof-image" src="{{ route('admin.member-orders.payments.proof', [$payment->order, $payment]) }}" alt="Bukti pembayaran transaksi {{ $payment->id }}"></a>
                @endif
            @else
                <div class="transaction-proof-placeholder">Tidak ada file bukti untuk catatan ini. Transaksi tanpa bukti tidak dapat diverifikasi melalui halaman ini.</div>
            @endif
        </section>
        <aside class="space-y-5 min-w-0">
            <section class="transaction-panel">
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="font-bold">{{ $payment->type_label }}</h2><x-status-badge :label="$payment->status_label" :color="match($payment->status) { 'verified' => '#bbf7d0', 'rejected' => '#fecaca', default => '#fef08a' }" /></div>
                <strong class="block text-3xl my-4 break-words">{{ $formatMoney($payment->amount) }}</strong>
                <dl class="transaction-facts">
                    <div><dt>Customer</dt><dd>{{ $payment->order->member->display_name }}</dd></div>
                    <div><dt>Pesanan</dt><dd><a href="{{ route('admin.member-orders.show', $payment->order) }}">{{ $payment->order->order_code }}</a></dd></div>
                    <div><dt>Batch</dt><dd>{{ $payment->order->batch->batch_name ?: $payment->order->batch->batch_number }}</dd></div>
                    <div><dt>Waktu transaksi</dt><dd>{{ $payment->paid_at?->format('d M Y, H:i:s') ?? 'Tidak tersedia' }}</dd></div>
                    <div><dt>Dikirim</dt><dd>{{ $payment->created_at?->format('d M Y, H:i') ?? 'Tidak tersedia' }}</dd></div>
                    <div><dt>Sumber</dt><dd>{{ $payment->customer_submitted ? 'Customer' : ($payment->creator ? 'Admin' : 'Data historis') }}</dd></div>
                    <div><dt>Sisa {{ $payment->type === 'ems' ? 'EMS & Pajak' : 'pesanan' }}</dt><dd>{{ $formatMoney($payment->type === 'ems' ? $payment->order->ems_tax_remaining_amount : $payment->order->remaining_amount) }}</dd></div>
                </dl>
                @if($payment->notes)<div class="transaction-note"><strong>Catatan pengirim</strong><p>{{ $payment->notes }}</p></div>@endif
                @if($payment->order->is_refunded)<p class="transaction-note">Pesanan direfund. Pembayaran ini tidak dapat disetujui.</p>@endif
            </section>
            <section class="transaction-panel">
                @if($payment->status === 'pending')
                    <h2 class="font-bold">Verifikasi pembayaran</h2><p class="text-sm text-zinc-500 mt-2">Persetujuan akan memperbarui tagihan. Jika bukti tidak sesuai, berikan alasan penolakan.</p>
                    <form class="mt-4 space-y-4" method="POST" action="{{ route('admin.transactions.review', $payment) }}">
                        @csrf
                        <label class="block text-sm font-semibold"><span class="block">Catatan verifikasi / alasan penolakan</span><textarea class="mt-2 block! w-full!" name="review_notes" rows="3" maxlength="1000">{{ old('review_notes') }}</textarea></label>
                        <div class="flex flex-wrap gap-2"><button class="admin-form-primary" type="submit" name="decision" value="verified" @disabled(!$payment->proof_path || $payment->order->is_refunded)>Verifikasi pembayaran</button><button class="admin-form-danger-action" type="submit" name="decision" value="rejected">Tolak bukti</button></div>
                    </form>
                @else
                    <h2 class="font-bold">Hasil pemeriksaan</h2><p class="text-sm text-zinc-500 mt-2">{{ $payment->status_label }}{{ $payment->reviewer ? ' oleh '.$payment->reviewer->name : '' }}{{ $payment->reviewed_at ? ' · '.$payment->reviewed_at->format('d M Y, H:i') : '' }}</p>
                    @if($payment->review_notes)<p class="transaction-note">{{ $payment->review_notes }}</p>@endif
                @endif
            </section>
        </aside>
    </div>
</x-layouts.app>
