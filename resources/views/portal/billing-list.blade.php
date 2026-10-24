<x-layouts.app title="Tagihan — Ocean Paws">
    <div class="portal-v2 portal-billing-v2 billing-finance">
        <div class="portal-v2-shell">
            <a class="portal-v2-back" href="{{ route('profile.show') }}" aria-label="Kembali ke profil"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali</span></a>
            <section class="portal-v2-hero portal-v2-hero-billing billing-finance-hero" aria-label="Ringkasan tagihan">
                <div class="portal-v2-hero-copy"><span class="portal-v2-kicker">PUSAT PEMBAYARAN</span><h1>Tagihan</h1><p>Kelola pembayaran yang masih harus diselesaikan dan lihat riwayat pembayaranmu.</p></div>
                <div class="portal-v2-hero-stat"><span class="portal-v2-stat-icon"><x-public-icon variant="rounded-bold" name="card" :size="26" /></span><small>Total yang harus dibayar</small><strong data-billing-total>Rp {{ number_format($outstandingTotal, fmod((float) $outstandingTotal, 1) > 0 ? 2 : 0, ',', '.') }}</strong><p data-billing-count>{{ $outstandingCount }} tagihan aktif</p></div>
            </section>

            <nav class="billing-finance-tabs" aria-label="Tab pembayaran" data-billing-tabs>
                <a href="{{ route('billing.index', request()->only('q')) }}" data-billing-tab="active" aria-controls="billing-active-panel" @if($tab === 'active') aria-current="page" @endif>Tagihan Aktif</a>
                <a href="{{ route('billing.index', ['tab' => 'history', ...request()->only('q')]) }}" data-billing-tab="history" aria-controls="billing-history-panel" @if($tab === 'history') aria-current="page" @endif>Riwayat Pembayaran</a>
            </nav>

            @if($user && !$member)
                <div class="portal-v2-empty"><small>AKUN ADMIN</small><h2>Tagihan customer tidak tersedia di akun admin.</h2><p>Gunakan akun customer untuk membuka pembayaran pribadi.</p><a href="{{ route('admin.dashboard') }}">Buka dashboard</a></div>
            @elseif(!$member)
                <div class="portal-v2-empty"><small>TAGIHAN PRIBADI</small><h2>Login untuk melihat tagihanmu.</h2><p>Tagihan dan riwayat pembayaran ditampilkan setelah akun customer terhubung.</p><a href="{{ route('login') }}">Login</a></div>
            @else
                <div id="billing-active-panel" data-billing-panel="active" @if($tab !== 'active') hidden @endif>
                    <form class="portal-order-toolbar billing-finance-toolbar" action="{{ route('billing.index') }}" method="GET" role="search" aria-label="Cari tagihan" data-clear-search-on-empty>
                        <label class="billing-finance-search"><span>Cari tagihan</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Kode pesanan atau nama batch"></label>
                        <label class="portal-order-sort"><span>Urutkan</span><select name="sort" data-portal-select2><option value="newest" @selected(request('sort', 'newest') === 'newest')>Terbaru</option><option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option><option value="amount_desc" @selected(request('sort') === 'amount_desc')>Nominal tertinggi</option><option value="amount_asc" @selected(request('sort') === 'amount_asc')>Nominal terendah</option></select></label>
                        <button type="submit" aria-label="Terapkan pencarian" title="Cari"><x-public-icon variant="rounded-bold" name="search" :size="18" /><span class="portal-search-submit-label">Terapkan</span></button>
                    </form>
                    <section class="billing-finance-bills" aria-label="Tagihan aktif">
                        @forelse($obligations as $obligation)
                            @php
                                $order = $obligation['order'];
                                $billLabel = match($obligation['label']) { 'DP' => 'DP Pesanan', 'Pelunasan' => 'Pelunasan Pesanan', 'Pembayaran pesanan' => 'Pembayaran Pesanan', default => $obligation['label'] };
                                $paymentLabel = match($obligation['label']) { 'DP' => 'Menunggu DP', 'Pelunasan' => 'Menunggu Pelunasan', default => 'Belum Dibayar' };
                                $hasPendingProof = $order->payments->contains(fn ($payment) => $payment->status === 'pending' && ($payment->type === 'ems') === ($obligation['type'] === 'ems'));
                            @endphp
                            <article class="billing-finance-bill" data-billing-type="{{ $obligation['type'] }}">
                                <header><h2>{{ $billLabel }}</h2><x-status-badge :label="$hasPendingProof ? 'Menunggu verifikasi' : $paymentLabel" color="#fef08a" /></header>
                                <div class="billing-finance-balance"><span>{{ $obligation['type'] === 'ems' ? 'Nominal tagihan' : ($order->next_payment['type'] === 'dp' ? 'DP yang harus dibayar' : 'Sisa pembayaran') }}</span><strong>Rp {{ number_format($obligation['amount'], fmod($obligation['amount'], 1) > 0 ? 2 : 0, ',', '.') }}</strong></div>
                                @if($obligation['due_date'])<p class="billing-finance-due">Jatuh tempo <time datetime="{{ $obligation['due_date']->toDateString() }}">{{ $obligation['due_date']->translatedFormat('d F Y') }}</time></p>@endif
                                <dl class="billing-finance-reference"><div><dt>Batch</dt><dd>{{ $order->batch->batch_name ?: $order->batch->batch_number }}</dd></div><div><dt>Kode pesanan</dt><dd>{{ $order->order_code }}</dd></div></dl>
                                <x-order-payment-method :order="$order" :bill-type="$obligation['type']" />
                                @if($obligation['type'] === 'order' && $order->payment_proof_path)<a class="billing-finance-proof" href="{{ route('profile.orders.payment-proof', $order) }}" target="_blank" rel="noopener">Lihat bukti pembayaran awal</a>@endif
                            </article>
                        @empty
                            <div class="billing-finance-empty">
                                @if($outstandingCount > 0)<h2>Tidak ada tagihan yang cocok.</h2><p>Coba kode pesanan atau nama batch lainnya.</p><a href="{{ route('billing.index') }}">Tampilkan semua tagihan</a>
                                @else<h2>Tidak ada tagihan aktif.</h2><p>Tidak ada pembayaran yang perlu diselesaikan saat ini. Riwayat pembayaran tetap tersedia pada tab berikutnya.</p>@endif
                            </div>
                        @endforelse
                    </section>
                </div>

                <div id="billing-history-panel" data-billing-panel="history" @if($tab !== 'history') hidden @endif>
                    <form class="portal-order-toolbar billing-finance-toolbar is-history" action="{{ route('billing.index') }}" method="GET" role="search" aria-label="Cari pembayaran" data-clear-search-on-empty>
                        <input type="hidden" name="tab" value="history">
                        <label class="billing-finance-search"><span>Cari pembayaran</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Kode pesanan atau nama batch"></label>
                        <button type="submit" aria-label="Terapkan pencarian" title="Cari"><x-public-icon variant="rounded-bold" name="search" :size="18" /><span class="portal-search-submit-label">Terapkan</span></button>
                    </form>
                    <section class="billing-finance-history" aria-label="Riwayat pembayaran">
                        @forelse($payments->groupBy(fn ($payment) => ($payment->paid_at ?? $payment->created_at)?->format('Y-m') ?? 'undated') as $monthPayments)
                            @php($monthDate = $monthPayments->first()->paid_at ?? $monthPayments->first()->created_at)
                            <section class="billing-finance-month">
                                <h2>{{ $monthDate?->translatedFormat('F Y') ?? 'Tanggal belum tersedia' }}</h2>
                                <div class="billing-finance-ledger">
                                    @foreach($monthPayments as $payment)
                                        @php($paidAt = $payment->paid_at ?? $payment->created_at)
                                        <article class="billing-transaction" data-payment-id="{{ $payment->id }}">
                                            <div class="billing-transaction-main"><h3>{{ $payment->type_label }}</h3><a href="{{ $payment->order->billing_tracking_url }}#{{ $payment->type === 'ems' ? 'ems-bill' : 'payment-summary' }}">{{ $payment->order->batch->batch_name ?: $payment->order->batch->batch_number }} · {{ $payment->order->order_code }}</a>@if($paidAt)<time datetime="{{ $paidAt->toIso8601String() }}">{{ $paidAt->translatedFormat('d F Y') }}</time>@else<small>Tanggal belum tersedia</small>@endif @if($payment->order->is_refunded)<small>Pesanan direfund</small>@endif</div>
                                            <div class="billing-transaction-value"><strong>{{ $payment->type === 'refund' ? '−' : '' }}Rp {{ number_format((float) $payment->amount, fmod((float) $payment->amount, 1) > 0 ? 2 : 0, ',', '.') }}</strong><x-status-badge label="Terverifikasi" color="#bbf7d0" /></div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @empty
                            <div class="billing-finance-empty">@if(request()->filled('q'))<h2>Tidak ada pembayaran yang cocok.</h2><p>Coba kode pesanan, batch, atau jenis pembayaran lainnya.</p><a href="{{ route('billing.index', ['tab' => 'history']) }}">Tampilkan semua pembayaran</a>@else<h2>Belum ada riwayat pembayaran.</h2><p>Pembayaran yang telah diverifikasi akan tercatat di sini.</p>@endif</div>
                        @endforelse
                    </section>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
