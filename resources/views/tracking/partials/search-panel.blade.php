<style>
/* Scoped to the search content; shared navigation and footer retain their styles. */
.ocean-tracking .tracking-neo-page.tracking-classic {
    --classic-ink: #355a45;
    --classic-outline: #496d54;
    --classic-card-shadow: #eba4bc;
    --classic-control-shadow: #90ba9b;
    --classic-button-shadow: #4d8156;
    --classic-paper: #fffaf1;
    --classic-pink: #f8b6cc;
    --classic-search-card: var(--pv2-paper);
    --classic-search-shadow: var(--pv2-shadow-color);
    --classic-matcha: #96c88a;
    --classic-title-pink: #f7bad0;
    --classic-title-green: #8fbd99;
    --classic-title-pink-shadow: #eba4bc;
    --classic-title-green-shadow: #91bd9b;
    --classic-icon-chip: #dce9cf;
    --portal-icon-fill-cream: #bce3cf;
    padding: 36px 0 140px;
    min-height: calc(100svh - 260px);
    font-family: "Nunito Sans", sans-serif;
}
.ocean-tracking .tracking-classic .tracking-neo-shell {
    width: min(89.3%, 1180px);
    margin: 0 auto;
}
.ocean-tracking .tracking-classic .tracking-neo-heading {
    position: relative; max-width: none; min-height: 0; overflow: hidden; margin: 0 0 24px;
    border: 3px solid var(--classic-outline); border-radius: 24px; padding: 28px;
    background: var(--classic-pink); box-shadow: var(--customer-shadow-card) var(--classic-card-shadow);
}
.ocean-tracking .tracking-classic .tracking-neo-heading::before,
.ocean-tracking .tracking-classic .tracking-neo-heading::after {
    content: ""; position: absolute; z-index: 0; border: 2px solid var(--classic-outline);
    border-radius: 50%; pointer-events: none;
}
.ocean-tracking .tracking-classic .tracking-neo-heading::before { top: -30px; right: 70px; width: 78px; height: 78px; background: var(--classic-title-green); }
.ocean-tracking .tracking-classic .tracking-neo-heading::after { right: 18px; bottom: -18px; width: 44px; height: 44px; background: var(--classic-paper); }
.ocean-tracking .tracking-classic .tracking-neo-eyebrow {
    position: relative; z-index: 1; min-height: 0; padding: 7px 10px; border: 2px solid var(--classic-outline);
    border-radius: 8px; background: var(--classic-paper); color: var(--classic-ink);
    box-shadow: var(--customer-shadow-control) var(--classic-control-shadow); font-size: 10px; font-weight: 900; letter-spacing: .12em;
}
.ocean-tracking .tracking-classic .tracking-neo-heading h1 {
    position: relative; z-index: 1; margin: 16px 0 0; font-family: var(--pv2-heading, "Nunito Sans", sans-serif);
    font-size: clamp(36px, 5vw, 62px); font-weight: 900; line-height: 1.02;
    letter-spacing: -.065em; color: var(--classic-ink) !important; -webkit-text-stroke: 0; paint-order: normal;
}
.ocean-tracking .tracking-classic .tracking-neo-heading h1 span {
    display: inline; width: auto; text-shadow: none;
}
.ocean-tracking .tracking-classic .tracking-neo-heading h1 span:first-child { margin-right: .18em; color: inherit; }
.ocean-tracking .tracking-classic .tracking-neo-heading h1 span:last-child { color: inherit; }
.ocean-tracking .tracking-classic .tracking-neo-heading p {
    position: relative; z-index: 1; max-width: 650px; margin: 10px 0 0; color: var(--classic-ink) !important;
    font-size: 13px; font-weight: 600; line-height: 1.6;
}
.ocean-tracking .tracking-classic .tracking-neo-search-card {
    width: 100%; max-width: none; overflow: visible; margin: 0; border: 2px solid var(--classic-outline);
    border-radius: 16px; padding: 16px; background: var(--classic-search-card);
    box-shadow: var(--customer-shadow-card) var(--classic-search-shadow);
}
.ocean-tracking .tracking-classic .tracking-neo-form {
    display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: stretch; gap: 12px; padding: 0; margin: 0;
}
.ocean-tracking .tracking-classic .tracking-neo-input-wrap {
    min-height: 44px; gap: 9px; padding: 0 12px; border: 2px solid var(--classic-outline);
    border-radius: 10px; background: #fff; box-shadow: none;
}
.ocean-tracking .tracking-classic .tracking-neo-input-icon { color: var(--classic-ink); }
.ocean-tracking .tracking-classic .tracking-neo-input-icon svg { width: 20px; height: 20px; }
.ocean-tracking .tracking-classic .tracking-neo-input-wrap input {
    min-height: 40px; font-family: "Nunito Sans", sans-serif; font-size: 12px; font-weight: 600; color: var(--classic-ink);
}
.ocean-tracking .tracking-classic .tracking-neo-input-wrap input::placeholder { color: #858880; opacity: 1; }
.ocean-tracking .tracking-classic .tracking-neo-input-wrap:focus-within { outline: 3px solid var(--classic-control-shadow); outline-offset: 2px; }
.ocean-tracking .tracking-classic .tracking-neo-form button {
    width: auto; min-width: 0; min-height: 44px; gap: 8px; padding: 8px 14px;
    border: 2px solid var(--classic-outline); border-radius: 10px;
    background: var(--classic-matcha); color: var(--classic-ink);
    box-shadow: var(--customer-shadow-control) var(--classic-control-shadow); font-size: 12px; font-weight: 900;
}
.ocean-tracking .tracking-classic .tracking-neo-form button:hover { transform: translate(-2px, 2px); box-shadow: var(--customer-shadow-pressed) var(--classic-control-shadow); }
.ocean-tracking .tracking-classic .tracking-neo-form button:focus-visible { outline: 3px solid var(--classic-outline); outline-offset: 3px; }
.ocean-tracking .tracking-classic .tracking-neo-form button .tracking-search-button-icon { display: none; }
.ocean-tracking .tracking-classic .tracking-neo-hint {
    display: flex; align-items: center; gap: 9px; min-height: 0; margin: 12px 0 0; padding: 0;
    border: 0; background: transparent; color: #36534c; font-size: 11px; font-weight: 600; line-height: 1.4;
}
.ocean-tracking .tracking-classic .tracking-hint-stars {
    display: grid; width: 28px; height: 28px; flex: 0 0 28px; place-items: center;
    border: 2px solid var(--classic-outline); border-radius: 9px; background: var(--classic-matcha);
    box-shadow: var(--customer-shadow-control) var(--classic-control-shadow); color: var(--classic-ink);
}
.ocean-tracking .tracking-classic .tracking-hint-stars .public-icon { width: 16px; height: 16px; }
.ocean-tracking .tracking-classic .tracking-neo-error { margin: 18px 0 0; }
@media (min-width: 768px) {
    .ocean-tracking .tracking-neo-page.tracking-classic { padding-top: 48px; padding-bottom: 130px; }
    .ocean-tracking .tracking-classic .tracking-neo-search-card { padding: 18px; }
}
@media (max-width: 600px) {
    .ocean-tracking .tracking-classic .tracking-neo-shell { width: calc(100% - 28px); }
    .ocean-tracking .tracking-classic .tracking-neo-heading { margin-bottom: 16px; border-radius: 18px; padding: 18px; }
    .ocean-tracking .tracking-classic .tracking-neo-heading h1 { margin-top: 12px; font-size: clamp(34px, 9vw, 46px); }
    .ocean-tracking .tracking-classic .tracking-neo-heading p { margin-top: 8px; font-size: 12px; }
    .ocean-tracking .tracking-classic .tracking-neo-search-card { padding: 12px; }
    .ocean-tracking .tracking-classic .tracking-neo-form { grid-template-columns: minmax(0, 1fr) 44px; gap: 8px; }
    .ocean-tracking .tracking-classic .tracking-neo-input-wrap { padding-inline: 9px; }
    .ocean-tracking .tracking-classic .tracking-neo-form button { width: 44px; min-height: 44px; justify-content: center; gap: 0; padding: 0; }
    .ocean-tracking .tracking-classic .tracking-neo-form button > span,
    .ocean-tracking .tracking-classic .tracking-neo-form button > .public-icon:last-child { display: none; }
    .ocean-tracking .tracking-classic .tracking-neo-form button .tracking-search-button-icon { display: inline-flex; }
}
html[data-customer-theme="dark"] .ocean-tracking .tracking-classic {
    --classic-ink: #e6f2e6; --classic-outline: #9dc5a4; --classic-paper: #284338; --classic-pink: #6a4050; --classic-matcha: #54784b;
    --classic-card-shadow: #081810; --classic-control-shadow: #081810; --classic-button-shadow: #081810;
    --classic-search-card: var(--tracking-pink); --classic-search-shadow: #081810;
    --classic-title-pink: #6a4050; --classic-title-green: #426e51;
    --classic-title-pink-shadow: #081810; --classic-title-green-shadow: #081810;
    --classic-icon-chip: #31483a;
    --portal-icon-fill-cream: #b9ddbd;
}
html[data-customer-theme="dark"] .ocean-tracking .tracking-classic .tracking-neo-hint { color: #e7efe7; }
</style>
<section class="tracking-neo-page tracking-classic" aria-labelledby="order-search-title">
    <div class="tracking-neo-shell">
        <header class="tracking-neo-heading">
            <span class="tracking-neo-eyebrow">TRACKING PESANAN</span>
            <h1 id="order-search-title"><span>Cari</span><span>pesananmu.</span></h1>
            <p>Masukkan kode pesanan atau username untuk melihat status dan riwayat pesanan.</p>
        </header>

        <section class="tracking-neo-search-card" aria-label="Form pencarian pesanan">
            <form method="POST" action="{{ route('tracking.search') }}" class="tracking-neo-form" role="search" aria-label="Cari tracking dan riwayat pesanan">
                @csrf
                <label for="tracking-query" class="sr-only">Kode pesanan atau username</label>
                <div class="tracking-neo-input-wrap">
                    <span class="tracking-neo-input-icon"><x-public-icon name="search" :size="19" /></span>
                    <input id="tracking-query" type="search" name="query" value="{{ old('query', $searchQuery ?? '') }}" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="255" placeholder="masukkan kode pesanan" aria-describedby="tracking-query-hint{{ $errors->has('query') ? ' tracking-query-error' : '' }}" @if($errors->has('query')) aria-invalid="true" autofocus @endif required>
                </div>
                <button type="submit" aria-label="Lacak pesanan">
                    <span class="tracking-search-button-icon" aria-hidden="true"><x-public-icon variant="rounded-bold" name="search" :size="20" /></span>
                    <span>Lacak pesanan</span>
                    <x-public-icon name="chevron-right" :size="20" />
                </button>
            </form>

            <div id="tracking-query-hint" class="tracking-neo-hint">
                <span class="tracking-hint-stars" aria-hidden="true"><x-public-icon variant="rounded-bold" name="sparkle" :size="21" /></span>
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
                            <x-order-ems-bill :order="$orderResult" />
                        </div>
                        <a class="tracking-result-action" href="{{ URL::temporarySignedRoute('tracking.progress', now()->addMinutes(15), ['orderCode' => $orderResult->order_code]) }}">Lihat perjalanan lengkap <x-public-icon name="arrow-right" :size="15" /></a>
                    </aside>
                </div>

            </article>
        @endisset

        @isset($memberResult)
            <article class="tracking-result-card tracking-history-card" data-smart-search-result="history">
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
