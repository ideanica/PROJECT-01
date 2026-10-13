<x-layouts.app title="Riwayat Pesanan — Ocean Paws">
    <style>
        .portal-orders-v2 .portal-v2-section-head{display:flex;min-height:86px;align-items:center;justify-content:space-between;gap:18px;margin-bottom:20px;border:2px solid var(--pv2-outline);border-radius:16px;padding:14px 16px;background:var(--pv2-pink);box-shadow:var(--customer-shadow-card) var(--pv2-shadow-color)}
        .portal-orders-v2 .portal-v2-section-head-main{display:flex;min-width:0;align-items:center;gap:13px}.portal-orders-v2 .portal-v2-section-head-icon{display:grid;width:46px;height:46px;flex:0 0 auto;place-items:center;border:2px solid var(--pv2-outline);border-radius:12px;background:var(--pv2-section-accent);color:var(--pv2-accent-text);box-shadow:var(--customer-shadow-control) var(--pv2-shadow-color)}
        .portal-orders-v2 .portal-v2-section-copy{min-width:0}.portal-orders-v2 .portal-v2-section-copy>span{display:block;color:var(--pv2-pink-strong);font-size:9px;font-weight:900;letter-spacing:.13em}.portal-orders-v2 .portal-v2-section-copy h2{margin:4px 0 0;font-family:var(--pv2-heading);font-size:24px;line-height:1.05;letter-spacing:-.035em}
        .portal-orders-v2 .portal-v2-section-count{display:flex;min-width:72px;align-items:baseline;justify-content:center;gap:4px;border:2px solid var(--pv2-outline);border-radius:12px;padding:9px 11px;background:var(--pv2-paper);box-shadow:var(--customer-shadow-control) var(--pv2-shadow-color)}.portal-orders-v2 .portal-v2-section-count strong{font-family:var(--pv2-heading);font-size:20px;line-height:1}.portal-orders-v2 .portal-v2-section-count span{color:var(--pv2-text);font-size:9px;font-weight:900;letter-spacing:.04em}
        @media(max-width:700px){.portal-orders-v2 .portal-v2-section-head{min-height:72px;border-radius:14px;padding:11px 12px;background:var(--pv2-paper);box-shadow:var(--customer-shadow-control) var(--pv2-shadow-color)}.portal-orders-v2 .portal-v2-section-head-icon{width:40px;height:40px}.portal-orders-v2 .portal-v2-section-copy h2{font-size:19px}.portal-orders-v2 .portal-v2-section-count{min-width:58px;padding:8px 9px}.portal-orders-v2 .portal-v2-section-count strong{font-size:17px}}
    </style>
    <div class="portal-v2 portal-orders-v2">
        <div class="portal-v2-shell">
            <a class="portal-v2-back" href="{{ route('profile.show') }}" aria-label="Kembali ke profil"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali</span></a>

            <section class="portal-v2-hero portal-v2-hero-orders"><div class="portal-v2-hero-copy"><span class="portal-v2-kicker">DATA MILIKMU</span><h1>Riwayat<br>pesanan</h1><p>Semua jajanan, pembayaran, dan perjalanan paketmu dirapikan dalam satu tempat.</p></div><div class="portal-v2-hero-stat"><span class="portal-v2-stat-icon"><x-public-icon variant="rounded-bold" name="history" :size="26" /></span><small>{{ $filter === 'all' ? 'SEMUA PESANAN' : 'HASIL FILTER' }}</small><strong>{{ $orders->count() }}</strong><p>dari {{ $allOrderCount }} pesanan</p></div></section>

            <div class="portal-v2-layout">
                @if($member)
                    <aside class="portal-v2-filter-panel"><div class="portal-v2-filter-head"><span>FILTER</span><small>Pilih status pesanan</small></div><nav class="portal-v2-filters" aria-label="Filter riwayat pesanan">
                        @foreach(['all'=>['Semua','bag'],'unpaid'=>['Belum bayar','wallet'],'active'=>['Aktif','sparkle'],'history'=>['Selesai','check-circle'],'shipping'=>['Dikirim','truck'],'refund'=>['Refund','history']] as $value => [$label,$icon])
                            <a class="{{ $filter === $value ? 'active' : '' }}" href="{{ route('orders.history', $value === 'all' ? [] : ['filter' => $value]) }}"><x-public-icon variant="rounded-bold" :name="$icon" :size="16" /><span>{{ $label }}</span><x-public-icon variant="rounded-bold" name="chevron-right" :size="13" /></a>
                        @endforeach
                    </nav></aside>
                @endif

                <section class="portal-v2-content {{ !$member ? 'is-full' : '' }}">
                    @if($user && !$member)
                        <div class="portal-v2-empty"><span class="portal-v2-empty-icon"><x-public-icon variant="rounded-bold" name="grid" :size="32" /></span><small>AKUN ADMIN</small><h2>Riwayat customer tidak tampil di sini.</h2><p>Gunakan akun customer untuk melihat riwayat pesanan pribadi.</p><a href="{{ route('admin.dashboard') }}">Buka dashboard</a></div>
                    @elseif(!$member)
                        <div class="portal-v2-empty"><span class="portal-v2-empty-icon"><x-public-icon variant="rounded-bold" name="bag" :size="32" /></span><small>RIWAYAT PRIBADI</small><h2>Login untuk membuka pesananmu.</h2><p>Pesanan dari semua batch akan otomatis terkumpul setelah akun customer terhubung.</p><a href="{{ route('login') }}">Login</a></div>
                    @else
                        <div class="portal-v2-section-head"><div class="portal-v2-section-head-main"><span class="portal-v2-section-head-icon"><x-public-icon variant="rounded-bold" name="bag" :size="18" /></span><div class="portal-v2-section-copy"><span>DAFTAR PESANAN</span><h2>{{ $filter === 'all' ? 'Semua pesananmu' : 'Pesanan terfilter' }}</h2></div></div><div class="portal-v2-section-count"><strong>{{ $orders->count() }}</strong><span>data</span></div></div>

                        <div class="portal-v2-order-grid">
                            @forelse($orders as $order)
                                @php
                                    $trackingStatus = $order->tracking_status;
                                    $orderTotal = (float) ($order->total_amount ?: $order->items->sum(fn ($item) => (float) ($item->subtotal ?: $item->unit_price * $item->quantity)));
                                    $itemCount = (int) $order->items->sum('quantity');
                                    $paidAmount = (float) $order->paid_amount;
                                    $remainingAmount = (float) $order->remaining_amount;
                                    $paymentLabel = $order->paymentStatus?->name ?: match($order->ledger_payment_state){'paid'=>'Lunas','partial'=>'DP / sebagian','unpaid'=>'Belum dibayar',default=>'Belum ditentukan'};
                                @endphp
                                <article class="portal-v2-order-card">
                                    <div class="portal-v2-card-top"><span class="portal-v2-thumb">@if($order->batch->catalog_image_path)<img src="{{ $order->batch->catalog_image_url }}" alt="">@else<x-public-icon variant="rounded-bold" name="box" :size="24" />@endif</span><div class="portal-v2-card-title"><small>{{ $order->order_code }}</small><h3>{{ $order->batch->batch_name ?: $order->batch->batch_number }}</h3><p>{{ $order->created_at->translatedFormat('d M Y') }}{{ $order->go_number ? ' · GO '.$order->go_number : '' }}</p></div>@if($trackingStatus)<x-status-badge :status="$trackingStatus" />@endif</div>

                                    <div class="portal-v2-price-box"><span>Total pesanan</span><strong>Rp {{ number_format($orderTotal, 0, ',', '.') }}</strong></div>

                                    <div class="portal-v2-meta-grid">
                                        <div><span>Jumlah item</span><strong>{{ $itemCount }} item</strong></div>
                                        <div><span>Pembayaran</span><strong>{{ $paymentLabel }}</strong></div>
                                        <div><span>Sudah dibayar</span><strong>Rp {{ number_format($paidAmount, 0, ',', '.') }}</strong></div>
                                        <div><span>Sisa</span><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></div>
                                    </div>

                                    <a class="portal-v2-card-action" href="{{ $order->history_tracking_url }}">Lihat rincian <x-public-icon variant="rounded-bold" name="arrow-right" :size="14" /></a>
                                </article>
                            @empty
                                <div class="portal-v2-empty portal-v2-empty-inline"><span class="portal-v2-empty-icon"><x-public-icon variant="rounded-bold" name="bag" :size="32" /></span><small>KOSONG</small><h2>Tidak ada pesanan di kategori ini.</h2><p>Coba kategori lain atau kembali ke profil.</p><a href="{{ route('profile.show') }}">Kembali ke Profil</a></div>
                            @endforelse
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-layouts.app>
