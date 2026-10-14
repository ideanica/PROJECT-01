<section class="tracking-neo-page" aria-labelledby="order-search-title">
    <div class="tracking-neo-shell">
        <header class="tracking-neo-heading">
            <span class="tracking-neo-eyebrow">TRACKING PESANAN</span>
            <h1 id="order-search-title">Cari pesananmu.</h1>
            <p>Masukkan kode pesanan atau username untuk melihat status dan riwayat pesanan.</p>
        </header>

        <section class="tracking-neo-search-card" aria-label="Form pencarian pesanan">
            <div class="tracking-neo-card-head">
                <span class="tracking-neo-card-icon"><x-public-icon name="search" :size="22" /></span>
                <div>
                    <span>CARI PESANAN</span>
                    <strong>Lacak status terbaru</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('tracking.search') }}" class="tracking-neo-form" role="search" aria-label="Cari tracking dan riwayat pesanan">
                @csrf
                <label for="tracking-query" class="sr-only">Kode pesanan atau username</label>
                <div class="tracking-neo-input-wrap">
                    <span class="tracking-neo-input-icon"><x-public-icon name="search" :size="19" /></span>
                    <input id="tracking-query" type="search" name="query" value="{{ old('query', $searchQuery ?? '') }}" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="255" placeholder="masukkan kode pesanan" aria-describedby="tracking-query-hint{{ $errors->has('query') ? ' tracking-query-error' : '' }}" @if($errors->has('query')) aria-invalid="true" autofocus @endif required>
                </div>
                <button type="submit">
                    <span>Lacak pesanan</span>
                    <x-public-icon name="arrow-right" :size="16" />
                </button>
            </form>

            <div id="tracking-query-hint" class="tracking-neo-hint">
                <x-public-icon name="sparkle" :size="14" />
                <span>Data mengikuti pembaruan terakhir dari admin.</span>
            </div>

            @error('query')
                <div id="tracking-query-error" class="tracking-neo-error" role="alert">
                    <x-public-icon name="search" :size="17" />
                    <span>{{ $message }}</span>
                </div>
            @enderror
        </section>
    </div>
</section>

@if(isset($orderResult) || isset($memberResult))
<section class="tracking-result-section" aria-live="polite">
    <div class="tracking-result-shell">
        @isset($orderResult)
            <article class="tracking-result-card" data-smart-search-result="tracking">
                <div class="tracking-result-titlebar"><strong>ORDER STATUS.EXE</strong><span class="op-window-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
                <header class="tracking-result-banner">
                    <div>
                        <span class="tracking-result-kicker"><x-public-icon name="box" :size="14" /> {{ $orderResult->batch->batch_number }}</span>
                        <h2>{{ $orderResult->order_code }}</h2>
                        <p>{{ $orderResult->batch->batch_name ?: 'Pesanan Ocean Paws' }} &middot; Diperbarui {{ $orderResult->updated_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <div class="tracking-current-status">
                        <small>STATUS TERKINI</small>
                        <x-status-badge :status="$orderResult->tracking_status" />
                        <span>{{ $orderResult->tracking_status?->description ?: 'Status pesanan mengikuti pembaruan terbaru dari admin.' }}</span>
                    </div>
                </header>

                <div class="tracking-result-layout">
                    <div class="tracking-result-main">
                        <div class="tracking-result-heading">
                            <div><span>RINCIAN PESANAN</span><h3>Barang yang sedang dipantau</h3></div>
                            <strong>{{ $orderResult->items->sum('quantity') }} item</strong>
                        </div>

                        <div class="tracking-result-items">
                            @forelse($orderResult->items as $item)
                                <div class="tracking-result-item">
                                    <span class="tracking-result-item-icon"><x-public-icon name="box" :size="18" /></span>
                                    <span><strong>{{ $item->item_name }}</strong><small>{{ $item->variant ?: 'Tanpa varian' }} &middot; Qty {{ $item->quantity }}</small></span>
                                    <x-status-badge :status="$orderResult->is_refunded ? $orderResult->tracking_status : ($item->overrideStatus ?: $orderResult->effective_status)" />
                                </div>
                            @empty
                                <p class="tracking-result-empty-copy">Belum ada item yang tercatat.</p>
                            @endforelse
                        </div>

                        @if($orderResult->batch->notes)
                            <div class="tracking-admin-note"><x-public-icon name="message" :size="18" /><span><strong>Catatan admin</strong><small>{{ $orderResult->batch->notes }}</small></span></div>
                        @endif
                    </div>

                    <aside class="tracking-result-side">
                        <div class="tracking-order-summary">
                            <span>RINGKASAN</span>
                            <div><small>Pelanggan</small><strong>{{ $orderResult->member->display_name }}</strong></div>
                            <div><small>Pembayaran</small><strong>{{ $orderResult->paymentStatus?->name ?: 'Belum ditentukan' }}</strong></div>
                            <div><small>Total barang</small><strong>Rp {{ number_format($orderResult->total_amount, 0, ',', '.') }}</strong></div>
                        </div>
                        <x-order-ems-bill :order="$orderResult" />
                        <a class="tracking-result-action" href="{{ URL::temporarySignedRoute('tracking.progress', now()->addMinutes(15), ['orderCode' => $orderResult->order_code]) }}">Lihat perjalanan lengkap <x-public-icon name="arrow-right" :size="15" /></a>
                    </aside>
                </div>
            </article>
        @endisset

        @isset($memberResult)
            <article class="tracking-result-card tracking-history-card" data-smart-search-result="history">
                <div class="tracking-result-titlebar"><strong>ORDER HISTORY.EXE</strong><span class="op-window-dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
                <header class="tracking-result-banner">
                    <div>
                        <span class="tracking-result-kicker"><x-public-icon name="history" :size="14" /> RIWAYAT PEMBELIAN</span>
                        <h2>{{ $memberResult->display_name }}</h2>
                        <p>{{ '@'.$memberResult->username }} &middot; {{ $memberResult->orders->count() }} pesanan ditemukan</p>
                    </div>
                    <div class="tracking-current-status tracking-member-status">
                        <small>AKUN DITEMUKAN</small>
                        <strong>{{ $memberResult->orders->sum(fn ($order) => $order->items->sum('quantity')) }} item tercatat</strong>
                        <span>Buka setiap pesanan untuk melihat item, status, pembayaran, dan Pajak/EMS.</span>
                    </div>
                </header>

                <div class="tracking-history-list">
                    @forelse($memberResult->orders as $order)
                        <details>
                            <summary>
                                <span class="tracking-history-icon"><x-public-icon name="box" :size="18" /></span>
                                <span><strong>{{ $order->order_code }}</strong><small>{{ $order->batch->batch_name ?: $order->batch->batch_number }} &middot; {{ $order->items->sum('quantity') }} item</small></span>
                                <x-status-badge :status="$order->tracking_status" />
                                <x-public-icon name="chevron-right" :size="16" />
                            </summary>
                            <div class="tracking-history-detail">
                                @foreach($order->items as $item)
                                    <div><span><strong>{{ $item->item_name }}</strong><small>{{ $item->variant ?: 'Tanpa varian' }} &middot; Qty {{ $item->quantity }}</small></span><x-status-badge :status="$order->is_refunded ? $order->tracking_status : ($item->overrideStatus ?: $order->effective_status)" /></div>
                                @endforeach
                                <x-order-ems-bill :order="$order" compact />
                                <a href="{{ URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), ['memberCode' => $memberResult->member_code, 'memberOrder' => $order]) }}">Lihat detail & perjalanan <x-public-icon name="arrow-right" :size="14" /></a>
                            </div>
                        </details>
                    @empty
                        <p class="tracking-result-empty-copy">Belum ada riwayat pesanan untuk username ini.</p>
                    @endforelse
                </div>
            </article>
        @endisset


    </div>
</section>
@endif

