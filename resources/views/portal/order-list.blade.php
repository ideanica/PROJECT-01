<x-layouts.app :title="$title.' — Ocean Paws'">
    <div class="portal-v2 portal-orders-v2 {{ $member ? 'portal-order-workspace' : '' }}">
        <div class="portal-v2-shell">
            <a class="portal-v2-back" href="{{ route('profile.show') }}" aria-label="Kembali ke profil"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali</span></a>

            <section class="portal-v2-hero portal-v2-hero-orders"><div class="portal-v2-hero-copy"><span class="portal-v2-kicker">{{ $eyebrow }}</span><h1>{{ $title }}</h1><p>{{ $description }}</p></div><div class="portal-v2-hero-stat"><span class="portal-v2-stat-icon"><x-public-icon variant="rounded-bold" :name="$icon" :size="26" /></span><small>TOTAL DATA</small><strong>{{ $orders->count() }}</strong><p>pesanan</p></div></section>

            <section class="portal-v2-content is-full">
                @if($user && !$member)
                    <div class="portal-v2-empty"><span class="portal-v2-empty-icon"><x-public-icon variant="rounded-bold" name="grid" :size="32" /></span><small>AKUN ADMIN</small><h2>Data pesanan pribadi tidak tersedia.</h2><p>Gunakan akun customer untuk membuka data pesanan pribadi.</p><a href="{{ route('admin.dashboard') }}">Buka dashboard</a></div>
                @elseif(!$member)
                    <div class="portal-v2-empty"><span class="portal-v2-empty-icon"><x-public-icon variant="rounded-bold" :name="$icon" :size="32" /></span><small>PESANAN PRIBADI</small><h2>Login untuk melihat data pesananmu.</h2><p>Halaman ini hanya menampilkan pesanan yang terhubung dengan akun customer milikmu.</p><a href="{{ route('login') }}">Login</a></div>
                @else
                    <div class="portal-order-controls">
                        <div class="portal-v2-section-head"><div><span>DAFTAR PESANAN</span><h2>{{ $title }}</h2></div><strong>{{ $orders->count() }} data</strong></div>

                        <x-portal-order-toolbar />
                    </div>

                    <div class="portal-v2-order-grid" data-order-scroll role="region" aria-label="Daftar pesanan">
                        @forelse($orders as $order)
                            @php
                                $trackingStatus = $order->tracking_status;
                                $orderTotal = (float) ($order->total_amount ?: $order->items->sum(fn ($item) => (float) ($item->subtotal ?: $item->unit_price * $item->quantity)));
                                $itemCount = (int) $order->items->sum('quantity');
                            @endphp
                            <article class="portal-v2-order-card">
                                <div class="portal-v2-card-top"><span class="portal-v2-thumb">@if($order->batch->catalog_image_path)<img src="{{ $order->batch->catalog_image_url }}" alt="">@else<x-public-icon variant="rounded-bold" name="box" :size="24" />@endif</span><div class="portal-v2-card-title"><small>{{ $order->order_code }}</small><h3>{{ $order->batch->batch_name ?: $order->batch->batch_number }}</h3><p>{{ $order->created_at->translatedFormat('d M Y') }}{{ $order->go_number ? ' · GO '.$order->go_number : '' }}</p></div></div>

                                <div class="portal-v2-price-box"><span>Total pesanan</span><strong>Rp {{ number_format($orderTotal, 0, ',', '.') }}</strong></div>

                                <div class="portal-v2-meta-grid">
                                    <div><span>Jumlah item</span><strong>{{ $itemCount }} item</strong></div>
                                    @if($scope === 'shipping')
                                        <div><span>Status pengiriman</span><strong>{{ $trackingStatus?->name ?: 'Dalam pengiriman' }}</strong></div>
                                    @elseif($scope === 'refund')
                                        <div><span>Status</span><strong>Refund</strong></div>
                                    @endif
                                </div>

                                <div class="portal-v2-card-actions"><a class="portal-v2-card-action {{ $scope === 'shipping' ? '' : 'is-secondary is-detail-action' }}" href="{{ $order->portal_tracking_url }}">{{ $scope === 'shipping' ? 'Lihat tracking' : 'Lihat Detail' }} <x-public-icon variant="rounded-bold" :name="$scope === 'shipping' ? 'arrow-right' : 'eye'" :size="16" /></a></div>
                            </article>
                        @empty
                            <div class="portal-v2-empty portal-v2-empty-inline"><span class="portal-v2-empty-icon"><x-public-icon variant="rounded-bold" :name="$icon" :size="32" /></span><small>KOSONG</small><h2>{{ $emptyTitle }}</h2><p>{{ $emptyDescription }}</p><a href="{{ route('orders.history') }}">Lihat semua pesanan</a></div>
                        @endforelse
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.app>
