<x-layouts.app title="Pembayaran — Ocean Paws">
    <div class="portal-v2 customer-payment-page">
        <div class="portal-v2-shell">
            <a class="portal-v2-back" href="{{ route('billing.index') }}"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali ke tagihan</span></a>
            <header class="order-detail-hero customer-payment-heading">
                <div class="order-detail-hero-copy"><span>DETAIL TAGIHAN</span><h1>{{ $billType === 'ems' ? 'EMS & Pajak' : 'Pembayaran Pesanan' }}</h1><p>{{ $order->order_code }} · {{ $order->batch?->batch_name ?: $order->batch?->batch_number }}</p></div>
                <div class="order-detail-cover"><x-public-icon variant="rounded-bold" name="wallet" :size="28" /></div>
            </header>
            @php
                $payable = $billType === 'ems' ? $order->has_outstanding_ems_tax : $order->has_outstanding_order_payment;
                $method = $order->batch?->getEffectivePaymentMethod();
                $imageUrl = $order->batch?->payment_method_image_url;
                $owner = auth()->user()?->role === 'customer' && auth()->user()?->member_id === $order->member_id;
                $formId = $order->id.'-'.$billType;
                $isCurrent = old('payment_form', session('payment_form')) === $formId;
                $records = $owner ? $order->payments->filter(fn ($payment) => ($payment->type === 'ems') === ($billType === 'ems'))->sortByDesc('id') : collect();
                $pending = $records->firstWhere('status', 'pending');
                $latest = $records->first();
                $balance = $billType === 'ems' ? $order->ems_tax_remaining_amount : $order->remaining_amount;
                $schedule = $billType === 'order' ? $order->next_payment : null;
                $amountDue = $schedule['amount'] ?? $balance;
                $formatMoney = fn ($amount) => 'Rp '.number_format((float) $amount, fmod((float) $amount, 1) > 0 ? 2 : 0, ',', '.');
            @endphp

            @if($payable)
                <section class="order-detail-panel order-payment-method customer-payment-card">
                    <div class="order-payment-method-content">
                        <header class="payment-section-title"><span class="payment-section-title-icon"><x-public-icon variant="rounded-bold" name="wallet" :size="22" /></span><div><span>METODE PEMBAYARAN</span><h2>{{ $method?->name ?: 'QRIS' }}</h2></div></header>
                        @if($method?->account_name)<p class="order-payment-account"><span>Atas nama</span><strong>{{ $method->account_name }}</strong></p>@endif
                        @if($imageUrl)<div class="order-payment-qr"><img src="{{ $imageUrl }}" alt="Kode pembayaran {{ $method?->name ?: 'QRIS' }}" loading="lazy"></div>@endif
                        <dl class="payment-amount-summary">
                            @if($schedule)
                                <div><dt>Harga keseluruhan</dt><dd>{{ $formatMoney($schedule['total']) }}</dd></div>
                                @if($order->payment_type === 'dp' && ($order->dp_percentage !== null || $schedule['paid'] <= 0))<div><dt>DP ({{ number_format($schedule['percentage'], 2, ',', '.') }}%)</dt><dd>{{ $formatMoney($schedule['dp']) }}</dd></div>@endif
                                <div><dt>Sudah terverifikasi</dt><dd>{{ $formatMoney($schedule['paid']) }}</dd></div>
                                <div><dt>Sisa keseluruhan</dt><dd>{{ $formatMoney($schedule['remaining']) }}</dd></div>
                            @endif
                            <div class="payment-amount-due"><dt>{{ $pending ? 'Bukti menunggu verifikasi' : ($schedule && $schedule['type'] === 'dp' ? 'DP yang harus dibayar' : 'Yang harus dibayar sekarang') }}</dt><dd>{{ $formatMoney($pending?->amount ?? $amountDue) }}</dd></div>
                        </dl>
                        @if(!$method && !$imageUrl)
                            <p class="payment-feedback">QRIS belum tersedia. Hubungi admin untuk informasi pembayaran.</p>
                        @elseif($owner)
                            <div class="payment-submission">
                                <header class="payment-section-title"><span class="payment-section-title-icon"><x-public-icon variant="rounded-bold" name="document" :size="22" /></span><div><span>KONFIRMASI PEMBAYARAN</span><h2>Kirim bukti pembayaran</h2></div></header>
                                <p>Scan QRIS di atas, lalu kirim bukti pembayaranmu. DP atau lunas baru tercatat setelah admin memverifikasi bukti.</p>
                                @if($isCurrent && session('payment_status'))<p class="payment-feedback" role="status">{{ session('payment_status') }}</p>@endif
                                @if($pending)
                                    <div class="payment-feedback"><strong>Menunggu verifikasi admin</strong><p>{{ $formatMoney($pending->amount) }} · {{ $pending->type_label }}</p><p>Waktu transaksi: {{ $pending->paid_at?->translatedFormat('d F Y, H:i:s') ?? 'Tidak tersedia' }}</p><p>Tagihan belum berkurang. Tidak perlu mengirim bukti yang sama lagi.</p>@if($pending->proof_path)<a href="{{ route('billing.payments.proof', [$order, $pending]) }}" target="_blank" rel="noopener">Lihat bukti terkirim</a>@endif</div>
                                @else
                                    @if($latest?->status === 'rejected')<div class="payment-feedback is-error"><strong>Bukti sebelumnya ditolak</strong><p>{{ $latest->review_notes ?: 'Silakan periksa pembayaran dan kirim bukti yang sesuai.' }}</p></div>@endif
                                    <form method="POST" action="{{ route('billing.payments.store', $order) }}" enctype="multipart/form-data" class="payment-proof-form" data-payment-proof-form>
                                        @csrf
                                        <input type="hidden" name="payment_form" value="{{ $formId }}">
                                        <input type="hidden" name="bill_type" value="{{ $billType }}">
                                        <input type="hidden" name="submission_key" value="{{ $isCurrent ? old('submission_key', (string) \Illuminate\Support\Str::uuid()) : \Illuminate\Support\Str::uuid() }}">
                                        <input type="hidden" name="amount" value="{{ $amountDue }}">
                                        <p>Waktu transaksi dicatat otomatis saat bukti pembayaran dikirim.</p>
                                        <label>Bukti pembayaran<input type="file" name="proof" accept="image/jpeg,image/png,image/webp" required><small>Unggah satu foto JPG, PNG, atau WebP. Maksimal 4 MB.</small></label>
                                        @if($errors->any())<div class="payment-feedback is-error" role="alert">{{ $errors->first() }}<p>Jika mengirim ulang, pilih kembali file bukti pembayaran.</p></div>@endif
                                        <button type="submit">Kirim bukti pembayaran</button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <p class="payment-feedback">Login dengan akun pemilik pesanan untuk mengirim bukti pembayaran. <a href="{{ route('login') }}">Login</a></p>
                        @endif
                        @if($method?->instructions)<div class="order-payment-instructions"><strong>Petunjuk pembayaran</strong><p>{{ $method->instructions }}</p></div>@endif
                    </div>
                </section>
            @endif

            @if(!$payable)
                <div class="payment-feedback" role="status"><strong>{{ $order->is_refunded ? 'Pesanan direfund' : 'Tidak ada pembayaran yang perlu diselesaikan' }}</strong><p>Tagihan ini tidak menerima pengiriman bukti baru.</p></div>
            @endif
        </div>
    </div>
</x-layouts.app>
