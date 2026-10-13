<x-layouts.app title="Progress {{ $order->order_code }}">
    <main class="page customer-detail-page"><div class="page-mid">
        <div class="detail-topbar"><a class="top-back" href="{{ route('tracking.index') }}" aria-label="Kembali"><x-public-icon name="arrow-left" :size="18" /></a><strong>Tracking Pesanan</strong></div>
        <header class="customer-detail-heading"><span>DETAIL PERJALANAN</span><h1>{{ $order->order_code }}</h1><p>Informasi pesanan dan seluruh progresnya dirangkum dalam satu tampilan.</p></header>

        <article class="detail-record-card customer-tracking-card">
            <header class="detail-record-hero">
                <div class="min-w-0">
                    <p class="detail-record-kicker">Kode Tracking</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h2 class="detail-record-title">{{ $order->order_code }}</h2>
                        <x-status-badge :status="$order->tracking_status" />
                    </div>
                    <p class="detail-record-description">Batch {{ $order->batch->batch_number }} · {{ $order->batch->batch_name ?: 'K-pop merchandise' }}</p>
                </div>
                <div class="detail-record-id">Diperbarui {{ $order->updated_at->format('d M Y') }}</div>
            </header>

            <section class="detail-record-section">
                <div class="detail-record-summary sm:grid-cols-3">
                    <div class="detail-record-field detail-record-field-violet"><span>Status terkini</span><strong>{{ $order->tracking_status?->name ?: 'Belum ada status' }}</strong></div>
                    <div class="detail-record-field detail-record-field-blue"><span>Jumlah item</span><strong>{{ $order->items->sum('quantity') }} item</strong></div>
                    <div class="detail-record-field detail-record-field-cyan"><span>Update terakhir</span><strong>{{ $order->updated_at->format('d M Y, H:i') }}</strong></div>
                </div>
                @if($order->batch->notes)
                    <div class="detail-record-note"><span>Informasi dari admin</span><p>{{ $order->batch->notes }}</p></div>
                @endif
                <x-order-ems-bill :order="$order" />
            </section>

            <section class="detail-record-section">
                <div class="detail-record-section-heading"><div><h3>Isi Pesanan</h3><p>Rincian produk beserta status masing-masing item.</p></div></div>
                <div class="tracking-product-list divide-y divide-zinc-100">
                    @forelse($order->items as $item)
                        <div class="tracking-product-row flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div class="tracking-product-copy min-w-0"><div class="font-semibold">{{ $item->item_name }}</div><div class="mt-1 text-sm text-zinc-500">{{ $item->variant ?: 'Tanpa varian' }} · Qty {{ $item->quantity }}</div></div>
                            <x-status-badge :status="$order->is_refunded ? $order->tracking_status : $item->effective_status" />
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">Belum ada item yang tercatat.</p>
                    @endforelse
                </div>
            </section>

            <section class="detail-record-section">
                <div class="detail-record-section-heading"><div><h3>Perjalanan Pesanan</h3><p>Pembaruan terbaru ditampilkan terlebih dahulu.</p></div></div>
                <div class="detail-record-timeline">
                    @forelse($timeline as $history)
                        <div class="detail-record-timeline-item">
                            <span class="detail-record-timeline-dot"></span>
                            <div class="font-semibold">{{ $history->newStatus?->name ?: 'Status diperbarui' }}</div>
                            <p>{{ $history->created_at->format('d M Y, H:i') }}{{ $history->note ? ' · '.$history->note : '' }}</p>
                        </div>
                    @empty
                        <p class="text-sm leading-6 text-zinc-500">Belum ada pembaruan perjalanan. Status terbaru tetap dapat dilihat di atas.</p>
                    @endforelse
                </div>
            </section>

            <footer class="detail-record-section bg-zinc-950 text-center text-sm text-white">
                Punya pesanan lain? <a class="font-semibold underline decoration-white/40 underline-offset-4 hover:decoration-white" href="{{ route('tracking.index') }}">Cari seluruh riwayat dengan username</a>
            </footer>
        </article>
    </div></main>
</x-layouts.app>

