<x-layouts.app title="Detail Pesanan {{ $order->order_code }}">
    @php
        $itemCount = $order->items->sum('quantity');
        $orderTotal = (float) ($order->total_amount ?: $order->items->sum(fn ($item) => (float) ($item->subtotal ?: $item->unit_price * $item->quantity)));
    @endphp

    <main class="page order-detail-page">
        <div class="order-detail-shell">
            <div class="order-detail-topbar">
                <a href="{{ URL::temporarySignedRoute('tracking.member', now()->addMinutes(15), ['memberCode' => $member->member_code]) }}" aria-label="Kembali ke riwayat">
                    <x-public-icon name="arrow-left" :size="17" />
                    <span>Kembali ke riwayat</span>
                </a>
                <span>DETAIL PESANAN</span>
            </div>

            <section class="order-detail-hero">
                <div class="order-detail-hero-copy">
                    <span>PESANAN {{ mb_strtoupper($member->display_name) }}</span>
                    <div class="order-detail-title-row">
                        <h1>{{ $order->order_code }}</h1>
                        <div class="order-detail-main-status"><x-status-badge :status="$order->tracking_status" /></div>
                    </div>
                    <p>Batch {{ $order->batch->batch_number }} · {{ $order->batch->batch_name ?: 'K-pop merchandise' }}</p>
                </div>

                <div class="order-detail-cover">
                    @if($order->batch->catalog_image_path)
                        <img src="{{ $order->batch->catalog_image_url }}" alt="{{ $order->batch->batch_name ?: $order->batch->batch_number }}">
                    @else
                        <x-public-icon name="box" :size="28" />
                    @endif
                </div>
            </section>

            <section class="order-detail-stats" aria-label="Ringkasan pesanan">
                <article>
                    <span class="order-detail-stat-icon"><x-public-icon name="box" :size="18" /></span>
                    <div><small>Total item</small><strong>{{ $itemCount }} item</strong></div>
                </article>
                <article>
                    <span class="order-detail-stat-icon"><x-public-icon name="card" :size="18" /></span>
                    <div><small>Total pesanan</small><strong>Rp {{ number_format($orderTotal, 0, ',', '.') }}</strong></div>
                </article>
                <article>
                    <span class="order-detail-stat-icon"><x-public-icon name="wallet" :size="18" /></span>
                    <div><small>Pembayaran</small><strong>{{ $order->paymentStatus?->name ?: $order->payment_type_label }}</strong></div>
                </article>
                <article>
                    <span class="order-detail-stat-icon"><x-public-icon name="calendar" :size="18" /></span>
                    <div><small>Diperbarui</small><strong>{{ $order->updated_at->translatedFormat('d M Y') }}</strong></div>
                </article>
            </section>

            <div class="order-detail-layout">
                <div class="order-detail-main-column">
                    <section class="order-detail-panel order-detail-items-panel">
                        <header class="order-detail-section-heading">
                            <div><span>ISI PESANAN</span><h2>Jajanan kamu</h2></div>
                            <b>{{ $order->items->count() }} variasi</b>
                        </header>

                        <div class="order-detail-item-list">
                            @forelse($order->items as $item)
                                <article class="order-detail-item">
                                    <span class="order-detail-item-icon"><x-public-icon name="bag" :size="18" /></span>
                                    <div class="order-detail-item-copy">
                                        <h3>{{ $item->item_name }}</h3>
                                        <p>{{ $item->variant ?: 'Tanpa variasi' }} · {{ $item->quantity }}×</p>
                                        @if($item->notes)<small>{{ $item->notes }}</small>@endif
                                    </div>
                                    <div class="order-detail-item-price">
                                        <strong>Rp {{ number_format((float) ($item->subtotal ?: $item->unit_price * $item->quantity), 0, ',', '.') }}</strong>
                                        <span>@if($order->is_refunded)<x-status-badge :status="$order->tracking_status" />@else<x-status-badge :status="$item->effective_status" />@endif</span>
                                    </div>
                                </article>
                            @empty
                                <div class="order-detail-empty"><x-public-icon name="bag" :size="22" /><p>Belum ada item yang tercatat.</p></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="order-detail-panel order-detail-timeline-panel">
                        <header class="order-detail-section-heading">
                            <div><span>PERJALANAN PESANAN</span><h2>Timeline status</h2></div>
                            <x-public-icon name="history" :size="20" />
                        </header>

                        <div class="order-detail-timeline">
                            @forelse($timeline as $history)
                                <article class="order-detail-timeline-entry">
                                    <span class="order-detail-timeline-dot"></span>
                                    <div class="order-detail-timeline-statuses">
                                        <x-status-badge :status="$history->oldStatus" />
                                        <span>menjadi</span>
                                        <x-status-badge :status="$history->newStatus" />
                                    </div>
                                    <p>{{ $history->note ?: 'Status pesanan diperbarui oleh admin.' }}</p>
                                    <time>{{ $history->created_at->translatedFormat('d M Y, H:i') }}</time>
                                </article>
                            @empty
                                <div class="order-detail-empty"><x-public-icon name="history" :size="22" /><p>Belum ada perjalanan status untuk pesanan ini.</p></div>
                            @endforelse
                        </div>
                    </section>
                </div>

                <aside class="order-detail-side-column">
                    <section class="order-detail-panel order-detail-payment-card">
                        <header><span class="order-detail-side-icon"><x-public-icon name="card" :size="19" /></span><div><small>PEMBAYARAN</small><h2>Ringkasan tagihan</h2></div></header>
                        <dl>
                            <div><dt>Jenis pembayaran</dt><dd>{{ $order->payment_type_label }}</dd></div>
                            <div><dt>Nominal dikirim</dt><dd>{{ $order->payment_amount ? 'Rp '.number_format($order->payment_amount, 0, ',', '.') : 'Belum ada' }}</dd></div>
                            <div><dt>Status pembayaran</dt><dd>{{ $order->paymentStatus?->name ?: 'Belum ditentukan' }}</dd></div>
                        </dl>
                        <x-order-payment-method :order="$order" />
                    </section>

                    @if($order->notes)
                        <section class="order-detail-panel order-detail-note-card">
                            <span>CATATAN PESANAN</span>
                            <p>{{ $order->notes }}</p>
                        </section>
                    @endif

                    @if($order->ems_tax_is_published)
                        <section class="order-detail-panel order-detail-ems-card">
                            <header class="order-detail-section-heading"><div><span>BIAYA TAMBAHAN</span><h2>EMS & Pajak</h2></div></header>
                            <x-order-ems-bill :order="$order" />
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </main>
</x-layouts.app>
