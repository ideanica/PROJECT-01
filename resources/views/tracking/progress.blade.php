<x-layouts.app title="Progress {{ $order->order_code }}">
    @php
        $shipmentGroups = $order->items->pluck('shipmentGroup')->filter()->unique('id')->values();
    @endphp

    <main class="page order-detail-page customer-detail-page">
        <div class="order-detail-shell">
            <div class="order-detail-topbar">
                <a class="portal-v2-back" href="{{ route('tracking.index') }}" aria-label="Kembali ke pencarian tracking"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali</span></a>
                <span>TRACKING PESANAN</span>
            </div>

            <section class="order-detail-hero">
                <div class="order-detail-hero-copy">
                    <span>DETAIL PERJALANAN</span>
                    <div class="order-detail-title-row"><h1>{{ $order->order_code }}</h1></div>
                    <p>Batch {{ $order->batch->batch_number }} · {{ $order->batch->batch_name ?: 'K-pop merchandise' }}{{ $order->go_number ? ' · GO '.$order->go_number : '' }}</p>
                </div>
                <div class="order-detail-cover">
                    <x-catalog-photo :batch="$order->batch" :alt="'Gambar ilustrasi katalog '.($order->batch->batch_name ?: $order->batch->batch_number)" loading="eager" />
                </div>
            </section>

            <section class="order-detail-panel order-detail-items-panel">
                <header class="order-detail-section-heading"><div><span>RINGKASAN</span><h2>Informasi pesanan</h2></div></header>
                <div class="order-detail-current-status"><span>Status saat ini</span><x-status-badge :status="$order->tracking_status" /></div>
                <dl class="tracking-progress-summary">
                    <div><dt>Jumlah item</dt><dd>{{ $order->items->sum('quantity') }} item</dd></div>
                    <div><dt>Pembaruan terakhir</dt><dd>{{ $order->updated_at->translatedFormat('d M Y, H:i') }}</dd></div>
                </dl>
                @if($order->batch->notes)<div class="order-detail-note-card tracking-progress-note"><span>INFORMASI DARI ADMIN</span><p>{{ $order->batch->notes }}</p></div>@endif
                <x-order-ems-bill :order="$order" />
            </section>

            <section class="order-detail-panel order-detail-items-panel">
                <header class="order-detail-section-heading"><div><span>ISI PESANAN</span><h2>Item pesanan</h2></div><b>{{ $order->items->count() }} variasi</b></header>
                <div class="order-detail-item-list">
                    @forelse($order->items as $item)
                        <article class="order-detail-item">
                            <span class="order-detail-item-icon order-detail-item-photo"><x-catalog-photo :batch="$order->batch" :context="$item->item_name" :alt="'Gambar ilustrasi item '.$item->item_name" /></span>
                            <div class="order-detail-item-copy">
                                <h3>{{ $item->item_name }}</h3>
                                <p>{{ $item->variant ?: 'Tanpa varian' }} · Qty {{ $item->quantity }}</p>
                                @if($item->shipmentGroup)<small>{{ $item->shipmentGroup->name }} · {{ str($item->shipmentGroup->method)->replace('_', ' ')->title() }}{{ $item->shipmentGroup->tracking_number ? ' · Resi '.$item->shipmentGroup->tracking_number : '' }}</small>@endif
                            </div>
                            <div class="order-detail-item-price"><x-status-badge :status="$order->is_refunded ? $order->tracking_status : $item->effective_status" /></div>
                        </article>
                    @empty
                        <div class="order-detail-empty"><x-public-icon variant="rounded-bold" name="bag" :size="22" /><p>Belum ada item yang tercatat.</p></div>
                    @endforelse
                </div>
            </section>

            @if($shipmentGroups->isNotEmpty())
                <section class="order-detail-panel order-detail-items-panel">
                    <header class="order-detail-section-heading"><div><span>INFORMASI PENGIRIMAN</span><h2>Pengiriman</h2></div><x-public-icon variant="rounded-bold" name="truck" :size="20" /></header>
                    <div class="tracking-progress-shipments">
                        @foreach($shipmentGroups as $shipment)
                            <article><div><strong>{{ $shipment->name }}</strong><p>{{ str($shipment->method)->replace('_', ' ')->title() }}</p></div><div class="tracking-progress-shipment-meta">@if($shipment->tracking_number)<p><span>Resi</span><strong>{{ $shipment->tracking_number }}</strong></p>@endif @if($shipment->shipment_code)<p><span>Kode</span><strong>{{ $shipment->shipment_code }}</strong></p>@endif @if($shipment->currentStatus)<x-status-badge :status="$shipment->currentStatus" />@endif</div></article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="order-detail-panel order-detail-timeline-panel">
                <header class="order-detail-section-heading"><div><span>PERJALANAN PESANAN</span><h2>Timeline status</h2></div><x-public-icon variant="rounded-bold" name="history" :size="20" /></header>
                @if($timeline->isEmpty() || $timeline->first()?->newStatus?->id !== $order->tracking_status?->id)
                    <div class="order-detail-current-status"><span>Status saat ini</span><x-status-badge :status="$order->tracking_status" /></div>
                @endif
                <div class="order-detail-timeline">
                    @forelse($timeline as $history)
                        <article class="order-detail-timeline-entry"><span class="order-detail-timeline-dot"></span><div class="order-detail-timeline-statuses">@if($loop->first && $history->newStatus?->id === $order->tracking_status?->id)<strong class="order-detail-current-label">Status saat ini</strong>@endif<x-status-badge :status="$history->newStatus" /></div><p>{{ $history->note ?: $history->newStatus?->description ?: 'Status pesanan diperbarui oleh admin.' }}</p><time>{{ $history->created_at->translatedFormat('d M Y, H:i') }}</time></article>
                    @empty
                        <div class="order-detail-empty"><x-public-icon variant="rounded-bold" name="history" :size="22" /><p>Belum ada pembaruan perjalanan. Status terbaru tetap dapat dilihat di atas.</p></div>
                    @endforelse
                </div>
            </section>

            <div class="tracking-progress-footer">Punya pesanan lain? <a href="{{ route('tracking.index') }}">Cari pesanan lainnya</a></div>
        </div>
    </main>
</x-layouts.app>
