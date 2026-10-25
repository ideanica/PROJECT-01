<x-layouts.app title="Detail Pesanan {{ $order->order_code }}">
    @php
        $itemCount = $order->items->sum('quantity');
        $orderTotal = (float) ($order->total_amount ?: $order->items->sum(fn ($item) => (float) ($item->subtotal ?: $item->unit_price * $item->quantity)));
        $paidAmount = (float) $order->paid_amount;
        $remainingAmount = (float) $order->remaining_amount;
        $verifiedPayments = $order->payments->where('status', 'verified')->sortByDesc(fn ($payment) => $payment->paid_at ?? $payment->created_at);
        $isCustomerOwner = auth()->user()?->role === \App\Models\User::ROLE_CUSTOMER
            && auth()->user()->member_id === $order->member_id;
        $canCancelOrder = $isCustomerOwner && $order->can_be_cancelled;
        $shipmentGroups = $order->items->pluck('shipmentGroup')->filter()->unique('id')
            ->reject(fn ($shipment) => mb_strtolower(trim($shipment->name)) === 'serah terima pesanan')
            ->values();
    @endphp

    <main class="page order-detail-page">
        <div class="order-detail-shell">
            <div class="order-detail-topbar">
                <a class="portal-v2-back" href="{{ $backUrl }}" aria-label="Kembali ke halaman sebelumnya"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali</span></a>
                <a href="{{ route('profile.show') }}" class="public-account-chip portal-v2-account-chip order-detail-topbar-profile" aria-label="Profil {{ $member->display_name }}" style="display:inline-flex;width:auto;height:42px;min-height:42px;align-items:center;gap:9px;border:2px solid #355a45;border-radius:12px;padding:5px;background:#fffdf8;color:#355a45;box-shadow:var(--customer-shadow-control) #eba4bc;text-decoration:none;">
                    @if($member->avatar_url)
                        <img src="{{ $member->avatar_url }}" alt="Foto profil {{ $member->display_name }}" style="width:28px;height:28px;flex:0 0 28px;border:2px solid #355a45;border-radius:9px;background:#f8d5df;object-fit:cover;box-shadow:var(--customer-shadow-pressed) #eba4bc;">
                    @else
                        <span aria-hidden="true" style="display:grid;width:28px;height:28px;flex:0 0 28px;place-items:center;border:2px solid #355a45;border-radius:9px;background:#f8d5df;color:#355a45;font-size:10px;font-weight:900;box-shadow:var(--customer-shadow-pressed) #eba4bc;">{{ mb_strtoupper(mb_substr($member->display_name, 0, 1)) }}</span>
                    @endif
                    <strong style="color:#355a45;font-family:'Trebuchet MS',Arial,system-ui,sans-serif;font-size:10px;font-weight:900;line-height:1;">{{ $member->display_name }}</strong>
                </a>
            </div>

            @if(session('status'))<p class="payment-feedback" role="status">{{ session('status') }}</p>@endif
            @if($errors->has('order'))<p class="payment-feedback is-error" role="alert">{{ $errors->first('order') }}</p>@endif

            <section class="order-detail-hero order-detail-title-card">
                <div class="order-detail-cover">
                    <x-catalog-photo :batch="$order->batch" :alt="'Gambar ilustrasi katalog '.($order->batch->batch_name ?: $order->batch->batch_number)" loading="eager" />
                </div>
                <div class="order-detail-hero-copy">
                    <span>KODE PESANAN</span>
                    <div class="order-detail-title-row"><h1>{{ $order->order_code }}</h1></div>
                    <p>Kode batch: {{ $order->batch->batch_number }}</p>
                </div>
            </section>

            {{--
            <section class="order-detail-stats" aria-label="Ringkasan pesanan">
                <article><span class="order-detail-stat-icon"><x-public-icon variant="rounded-bold" name="box" :size="18" /></span><div><small>Total item</small><strong>{{ $itemCount }} item</strong></div></article>
                <article><span class="order-detail-stat-icon"><x-public-icon variant="rounded-bold" name="card" :size="18" /></span><div><small>Total pesanan</small><strong>Rp {{ number_format($orderTotal, 0, ',', '.') }}</strong></div></article>
                <article><span class="order-detail-stat-icon"><x-public-icon variant="rounded-bold" name="wallet" :size="18" /></span><div><small>Sisa tagihan</small><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></div></article>
                <article><span class="order-detail-stat-icon"><x-public-icon variant="rounded-bold" name="calendar" :size="18" /></span><div><small>Diperbarui</small><strong>{{ $order->updated_at->translatedFormat('d M Y') }}</strong></div></article>
            </section>
            --}}

            <div class="order-detail-layout">
                <div class="order-detail-main-column">
                    <section class="order-detail-panel order-detail-items-panel">
                        <header class="order-detail-section-heading"><div><span>ISI PESANAN</span><h2>Item pesanan</h2></div><b>{{ $order->items->count() }} variasi</b></header>
                        <div class="order-detail-item-list">
                            @forelse($order->items as $item)
                                <article class="order-detail-item">
                                    <span class="order-detail-item-icon order-detail-item-photo"><x-catalog-photo :batch="$order->batch" :context="$item->item_name" :alt="'Gambar ilustrasi item '.$item->item_name" /></span>
                                    <div class="order-detail-item-copy">
                                        <h3>{{ $item->item_name }}</h3>
                                        <p>{{ $item->variant ?: 'Tanpa variasi' }} · {{ $item->quantity }}×</p>
                                        @if($item->shipmentGroup && mb_strtolower(trim($item->shipmentGroup->name)) !== 'serah terima pesanan')
                                            <small>{{ $item->shipmentGroup->name }} · {{ str($item->shipmentGroup->method)->replace('_', ' ')->title() }}{{ $item->shipmentGroup->tracking_number ? ' · Resi '.$item->shipmentGroup->tracking_number : '' }}</small>
                                        @elseif($item->notes)<small>{{ $item->notes }}</small>@endif
                                        @if(!$order->is_refunded && $item->override_status_id && $item->effective_status?->id !== $order->tracking_status?->id)
                                            <small>Kondisi item: {{ $item->effective_status?->name }}</small>
                                        @endif
                                    </div>
                                    <div class="order-detail-item-price"><strong>Rp {{ number_format((float) ($item->subtotal ?: $item->unit_price * $item->quantity), 0, ',', '.') }}</strong></div>
                                </article>
                            @empty
                                <div class="order-detail-empty"><x-public-icon variant="rounded-bold" name="bag" :size="22" /><p>Belum ada item yang tercatat.</p></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="order-detail-panel order-detail-timeline-panel">
                        <header class="order-detail-section-heading"><div><span>PERJALANAN PESANAN</span><h2>Timeline status</h2></div><x-public-icon variant="rounded-bold" name="history" :size="20" /></header>
                        @if($timeline->isEmpty() || $timeline->first()?->newStatus?->id !== $order->tracking_status?->id)
                            <div class="order-detail-current-status"><span>Status saat ini</span><x-status-badge :status="$order->tracking_status" /></div>
                        @endif
                        <div class="order-detail-timeline">
                            @forelse($timeline as $history)
                                <article class="order-detail-timeline-entry"><span class="order-detail-timeline-dot"></span><div class="order-detail-timeline-statuses">@if($loop->first && $history->newStatus?->id === $order->tracking_status?->id)<strong class="order-detail-current-label">Status saat ini</strong>@endif<x-status-badge :status="$history->newStatus" /></div><p>{{ $history->note ?: $history->newStatus?->description ?: 'Status pesanan diperbarui oleh admin.' }}</p><time>{{ $history->created_at->translatedFormat('d M Y, H:i') }}</time></article>
                            @empty
                                <div class="order-detail-empty"><x-public-icon variant="rounded-bold" name="history" :size="22" /><p>Belum ada perjalanan status untuk pesanan ini.</p></div>
                            @endforelse
                        </div>
                    </section>
                </div>

                <aside class="order-detail-side-column">
                    <section id="payment-summary" class="order-detail-panel order-detail-payment-card">
                        <header><span class="order-detail-side-icon"><x-public-icon variant="rounded-bold" name="card" :size="19" /></span><div><small>PEMBAYARAN</small><h2>Ringkasan tagihan</h2></div></header>
                        <dl>
                            <div><dt>Total pesanan</dt><dd>Rp {{ number_format($orderTotal, 0, ',', '.') }}</dd></div>
                            <div><dt>Sudah dibayar</dt><dd>Rp {{ number_format($paidAmount, 0, ',', '.') }}</dd></div>
                            <div><dt>Sisa tagihan</dt><dd>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</dd></div>
                            <div><dt>Status pembayaran</dt><dd>{{ $order->paymentStatus?->name ?: 'Belum ditentukan' }}</dd></div>
                            @if($order->is_refunded)<div><dt>Status pesanan</dt><dd>Direfund</dd></div>@endif
                            @if($order->is_cancelled)<div><dt>Status pesanan</dt><dd>Dibatalkan</dd></div>@endif
                        </dl>
                        @if($order->has_outstanding_order_payment)<x-order-payment-method :order="$order" />@endif
                        @if($order->ems_tax_is_published)
                            <div id="ems-bill" class="order-detail-tax-summary">
                                <x-order-ems-bill :order="$order" :show-status-badge="false" />
                                @if($order->has_outstanding_ems_tax)<x-order-payment-method :order="$order" bill-type="ems" />@endif
                            </div>
                        @endif
                        @if($verifiedPayments->isNotEmpty())
                            <details class="order-detail-payment-history portal-order-disclosure">
                                <summary>Riwayat pembayaran ({{ $verifiedPayments->count() }})</summary>
                                @foreach($verifiedPayments as $payment)
                                    <div><span>{{ match($payment->type) { 'dp' => 'DP', 'installment' => 'Cicilan', 'settlement' => 'Pelunasan', 'full_payment' => 'Pembayaran penuh', default => 'Pembayaran lainnya' } }}<br><small>{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('d M Y') }}</small></span><b>Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</b></div>
                                @endforeach
                            </details>
                        @endif
                        @if($canCancelOrder)
                            <div class="order-detail-cancel">
                                <p>Pesanan yang belum memiliki pembayaran atau bukti pembayaran dapat dibatalkan.</p>
                                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini? Pesanan yang dibatalkan tidak dapat dibayar kembali.');">
                                    @csrf
                                    <button class="order-detail-cancel-button" type="submit">Batalkan pesanan</button>
                                </form>
                            </div>
                        @endif
                    </section>

                    @if($shipmentGroups->isNotEmpty())
                        <section class="order-detail-panel order-detail-note-card">
                            <span>PENGIRIMAN</span>
                            @foreach($shipmentGroups as $shipment)
                                <div class="mt-3 first:mt-0"><strong>{{ $shipment->name }}</strong><p>{{ str($shipment->method)->replace('_', ' ')->title() }}{{ $shipment->tracking_number ? ' · Resi '.$shipment->tracking_number : '' }}</p>@if($shipment->currentStatus)<p>Status pengiriman: {{ $shipment->currentStatus->name }}</p>@endif</div>
                            @endforeach
                        </section>
                    @endif

                    @if($order->notes)<section class="order-detail-panel order-detail-note-card order-detail-order-note"><span>CATATAN PESANAN</span><p>{{ $order->notes }}</p></section>@endif
                </aside>
            </div>
        </div>
    </main>
</x-layouts.app>
