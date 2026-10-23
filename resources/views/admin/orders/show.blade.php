@php($isRefunded = $order->is_refunded)
<x-layouts.app title="Pesanan {{ $order->order_code }}">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Detail Pesanan" description="Ringkasan pelanggan, isi pesanan, dan progres pengiriman.">
            <x-slot:action><a class="admin-form-primary" href="{{ route('admin.member-orders.payments.index', $order, false) }}">Kelola pembayaran</a><a class="admin-form-secondary" href="{{ route('admin.member-orders.edit', $order, false) }}">Edit Pesanan</a></x-slot:action>
        </x-page-heading>
    </div>

    <article class="detail-record-card before:bg-none! [&_.detail-record-hero]:bg-[linear-gradient(135deg,#ffffff,#f6faf6_58%,#eef6ef)]!">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker text-[#275a3a]!">Pesanan Pelanggan</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="detail-record-title">{{ $order->order_code }}</h2>
                    <x-status-badge :status="$order->effective_status" />
                </div>
                <p class="detail-record-description">Diperbarui {{ $order->updated_at->format('d M Y, H:i') }}</p>
            </div>

        </header>

        <section class="detail-record-section">
            <div class="detail-record-summary sm:grid-cols-2 xl:grid-cols-4">
                <div class="detail-record-field detail-record-field-violet">
                    <span>Pelanggan</span>
                    <strong>{{ $order->member->display_name }}</strong>
                    <small>Username: {{ $order->member->username }}</small>
                </div>
                <a class="detail-record-field detail-record-field-link detail-record-field-blue" href="{{ route('admin.batches.show', $order->batch, false) }}">
                    <span>Batch</span>
                    <strong>{{ $order->batch->batch_number }}</strong>
                    <small>{{ $order->batch->batch_name ?: 'Tanpa nama batch' }}</small>
                </a>
                <div class="detail-record-field detail-record-field-cyan">
                    <span>Total pesanan</span>
                    <strong>{{ $order->total_amount ? 'Rp '.number_format($order->total_amount, 0, ',', '.') : 'Belum dihitung' }}</strong>
                </div>
                <div class="detail-record-field detail-record-field-amber">
                    <span>Status pembayaran</span>
                    <div class="detail-record-stat-badge mt-2">@if($order->paymentStatus)<x-status-badge :status="$order->paymentStatus" />@else<strong>{{ $order->payment_status ?: 'Belum ditentukan' }}</strong>@endif</div>
                </div>
            </div>
            @if($order->notes)
                <div class="detail-record-note"><span>Catatan pesanan</span><p>{{ $order->notes }}</p></div>
            @endif
            <div class="detail-record-note"><span>Alamat pengiriman</span><p class="whitespace-pre-line">{{ ($order->shipping_address ?? $order->member->address) ?: 'Belum diisi. Konfirmasikan alamat kepada customer sebelum pengiriman.' }}</p>
                @if($order->shipping_address !== null)<small>Alamat dipertahankan saat customer mengubah profil selama pengiriman.</small>@endif
            </div>
            <x-order-ems-bill :order="$order" show-empty />
        </section>

        @if($order->order_source === 'catalog' || $order->payment_proof_path)
            <section class="detail-record-section detail-record-section-tinted">
                <div class="detail-record-section-heading">
                    <div><h3>Pembayaran dari Katalog</h3><p>Data yang dikirim pelanggan melalui checkout batch.</p></div>
                    @if($order->payment_proof_path)<a class="admin-form-inline-action" href="{{ route('admin.member-orders.payment-proof', $order, false) }}" target="_blank" rel="noopener">Buka Bukti Bayar</a>@endif
                </div>
                <div class="detail-record-summary sm:grid-cols-2 xl:grid-cols-4">
                    <div class="detail-record-field detail-record-field-blue"><span>Jenis pembayaran</span><strong>{{ $order->payment_type_label }}</strong><small>{{ $order->payment_type === 'dp' ? 'Masih memiliki sisa pelunasan' : 'Tidak ada sisa pembayaran barang' }}</small></div>
                    <div class="detail-record-field detail-record-field-cyan"><span>Nominal dikirim</span><strong>{{ $order->payment_amount ? 'Rp '.number_format($order->payment_amount, 0, ',', '.') : '-' }}</strong><small>Dari total barang Rp {{ number_format($order->total_amount, 0, ',', '.') }}</small></div>
                    <div class="detail-record-field detail-record-field-violet"><span>Waktu submit</span><strong>{{ $order->payment_submitted_at?->format('d M Y, H:i') ?: '-' }}</strong><small>Sumber: katalog landing page</small></div>
                    <div class="detail-record-field detail-record-field-amber"><span>Status pembayaran</span><div class="detail-record-stat-badge mt-2">@if($order->paymentStatus)<x-status-badge :status="$order->paymentStatus" />@else<strong>{{ $order->payment_type_label }}</strong>@endif</div></div>
                </div>
            </section>
        @endif

        <section class="detail-record-section detail-record-section-tinted">
            <div class="detail-record-section-heading">
                <div>
                    <h3>Status Khusus Pesanan</h3>
                    <p>Bandingkan progress batch dengan kondisi khusus yang hanya berlaku untuk pesanan ini.</p>
                </div>
            </div>

            <div class="order-special-status-summary">
                <div class="order-special-status-stat">
                    <div>
                        <span>Status asli dari batch saat ini</span>
                        <small>{{ $order->batch->batch_number }} · otomatis mengikuti progress batch</small>
                    </div>
                    <x-status-badge :status="$order->batch->effective_status" />
                </div>
                <div class="order-special-status-stat">
                    <div>
                        <span>Status khusus pada pesanan</span>
                        <small>{{ $order->override_status_id ? 'Status ini menggantikan progress batch' : 'Belum ada status khusus; pesanan mengikuti batch' }}</small>
                    </div>
                    <div class="order-special-status-control">
                        @if($order->override_status_id)
                            <x-status-badge :status="$order->overrideStatus" />
                        @else
                            <strong>Tidak ada</strong>
                        @endif
                        @unless($isRefunded)
                            <button type="button" class="admin-form-inline-action" data-order-status-modal-open data-status-modal-open="order-special-status">Ubah Status</button>
                        @endunless
                    </div>
                </div>
            </div>

            @if($isRefunded)
                <div class="admin-form-lock-notice mt-3">
                    <strong>Pesanan selesai karena refund</strong>
                    <p>Progress telah ditutup, tidak lagi mengikuti batch, dan tidak dapat diberi status khusus maupun status lainnya.</p>
                </div>
            @endif

        </section>

        <section class="detail-record-section detail-record-table-section">
            <div class="detail-record-section-heading detail-record-table-heading">
                <div><h3>Item Pesanan</h3><p>{{ $order->items->sum('quantity') }} item dari {{ $order->items->count() }} produk tercatat.</p></div>
            </div>
            <div class="order-table-scroll">
                <table class="order-table responsive-card-table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th>Status item</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @forelse($order->items as $item)
                            <tr>
                                <td data-label="Item"><div class="order-table-primary">{{ $item->item_name }}</div><div class="order-table-secondary">{{ $item->variant ?: 'Tanpa varian' }}</div></td>
                                <td data-label="Qty" class="font-semibold text-zinc-700">{{ $item->quantity }}</td>
                                <td data-label="Harga" class="text-zinc-600">{{ $item->unit_price ? 'Rp '.number_format($item->unit_price, 0, ',', '.') : '-' }}</td>
                                <td data-label="Subtotal" class="font-semibold text-zinc-700">{{ $item->subtotal ? 'Rp '.number_format($item->subtotal, 0, ',', '.') : '-' }}</td>
                                <td data-label="Status item"><x-status-badge :status="$item->effective_status" /></td>
                                <td data-label="Catatan" class="max-w-xs text-zinc-600">{{ $item->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Belum ada item pada pesanan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <details class="detail-record-section">
            <summary class="cursor-pointer font-bold">Riwayat status ({{ $order->statusHistories->count() }})</summary>
            <div class="detail-record-timeline">
                @forelse($order->statusHistories as $history)
                    <div class="detail-record-timeline-item">
                        <span class="detail-record-timeline-dot"></span>
                        <div class="flex flex-wrap items-center gap-2"><x-status-badge :status="$history->oldStatus" /><span class="text-zinc-400">menjadi</span><x-status-badge :status="$history->newStatus" /></div>
                        <p>{{ $history->note ?: 'Tanpa catatan' }} · {{ $history->changedBy?->name ?: 'Sistem' }} · {{ $history->created_at->format('d M Y, H:i') }}</p>
                    </div>
                @empty
                    <p class="text-zinc-500">Belum ada riwayat perubahan status.</p>
                @endforelse
            </div>
        </details>
    </article>

    @unless($isRefunded)
    <div class="status-modal" data-order-status-modal data-status-modal="order-special-status" role="dialog" aria-modal="true" aria-labelledby="order-status-modal-title" aria-hidden="true" hidden>
        <button type="button" class="status-modal-backdrop" data-order-status-modal-close data-status-modal-close aria-label="Tutup modal"></button>
        <div class="status-modal-surface order-status-modal-surface" tabindex="-1">
            <header class="status-modal-header">
                <div>
                    <p>Status Khusus Pesanan</p>
                    <h2 id="order-status-modal-title">Atur Status {{ $order->order_code }}</h2>
                </div>
                <button type="button" class="status-modal-close" data-order-status-modal-close data-status-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>

            <div class="status-modal-body">
                <form method="POST" action="{{ route('admin.member-orders.status', $order, false) }}" class="order-status-override-form" data-order-status-override-form>
                    @csrf
                    <label>
                        <span>Status khusus</span>
                        <select name="status_id" @required(! $order->override_status_id)>
                            @if($order->override_status_id)
                                <option value="">Hapus status khusus · kembali mengikuti {{ $order->batch->effective_status?->name ?: 'status batch' }}</option>
                            @else
                                <option value="" selected disabled>Pilih status khusus</option>
                            @endif
                            @foreach($orderStatuses as $status)
                                <option value="{{ $status->id }}" @selected($order->override_status_id === $status->id)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Alasan perubahan</span>
                        <textarea name="note" rows="3" maxlength="1000" placeholder="Jelaskan mengapa progress pesanan ini berbeda dari batch"></textarea>
                    </label>
                    <div class="flex justify-end">
                        <button class="admin-form-primary" type="submit">Simpan Status Khusus</button>
                    </div>
                </form>
            </div>

            <footer class="status-detail-footer status-modal-footer">
                <p>Perubahan disimpan ke riwayat status agar dapat diaudit.</p>
                <button type="button" class="admin-form-secondary" data-order-status-modal-close data-status-modal-close>Tutup</button>
            </footer>
        </div>
    </div>
    @endunless
</x-layouts.app>
