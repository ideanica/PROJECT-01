<x-layouts.app title="Batch {{ $batch->batch_number }}">
    <x-page-heading title="Detail Batch" description="Seluruh informasi batch, progres, dan pesanan terkait dalam satu tampilan.">
        <x-slot:action>
            <div class="flex flex-wrap gap-2">
                @unless($batch->is_archived)
                    <a class="admin-form-primary" href="{{ route('admin.member-orders.create', ['batch_id' => $batch->id], false) }}">+ Tambah Pesanan</a>
                    <a class="admin-form-secondary" href="{{ route('admin.batches.edit', $batch, false) }}">Edit Batch</a>
                @endunless
            </div>
        </x-slot:action>
    </x-page-heading>

    <article class="detail-record-card">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker">Batch Pembelian</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="detail-record-title">{{ $batch->batch_number }}</h2>
                    <x-status-badge :status="$batch->currentStatus" />
                    <span class="detail-record-state {{ $batch->is_archived ? 'detail-record-state-muted' : 'detail-record-state-active' }}">
                        {{ $batch->is_archived ? 'Diarsipkan' : 'Aktif' }}
                    </span>
                </div>
                <p class="detail-record-description">{{ $batch->batch_name ?: 'Batch tanpa nama tambahan' }}</p>
            </div>
            <div class="detail-record-id">ID #{{ str_pad($batch->id, 4, '0', STR_PAD_LEFT) }}</div>
        </header>

        <section class="detail-record-section">
            <div class="detail-record-summary sm:grid-cols-2 xl:grid-cols-4">
                <div class="detail-record-field detail-record-field-violet">
                    <span>Periode mulai</span>
                    <strong>{{ $batch->started_at?->format('d M Y, H:i') ?: 'Belum ditentukan' }}</strong>
                </div>
                <div class="detail-record-field detail-record-field-blue">
                    <span>Periode selesai</span>
                    <strong>{{ $batch->completed_at?->format('d M Y, H:i') ?: 'Belum selesai' }}</strong>
                </div>
                <div class="detail-record-field detail-record-field-cyan">
                    <span>Jumlah pesanan</span>
                    <strong>{{ $batch->orders->count() }} pesanan</strong>
                </div>
                <div class="detail-record-field detail-record-field-amber">
                    <span>Total item</span>
                    <strong>{{ $batch->orders->sum(fn ($order) => $order->items->sum('quantity')) }} item</strong>
                </div>
            </div>
        </section>

        <section class="detail-record-section">
            <div class="detail-record-section-heading">
                <div>
                    <h3>Katalog Pre-Order</h3>
                    <p>Gambar, periode pemesanan, QRIS, dan variasi yang tampil pada landing page.</p>
                </div>
                <span class="detail-record-state {{ $batch->is_catalog_visible ? 'detail-record-state-active' : 'detail-record-state-muted' }}">{{ $batch->is_catalog_visible ? 'Tampil di katalog' : 'Disembunyikan' }}</span>
            </div>

            <div class="batch-catalog-admin-summary">
                <div class="batch-catalog-admin-image">
                    @if($batch->catalog_image_path)<img src="{{ $batch->catalog_image_url }}" alt="{{ $batch->batch_name }}">@else<i class="bi bi-image" aria-hidden="true"></i><span>Belum ada gambar katalog</span>@endif
                </div>
                <div class="detail-record-field detail-record-field-blue">
                    <span>Batas pemesanan</span>
                    <strong>{{ $batch->ordering_deadline?->format('d M Y, H:i') ?: 'TBA' }}</strong>
                    <small>{{ $batch->catalog_is_open ? 'Katalog masih menerima pesanan' : 'Katalog belum dibuka atau sudah ditutup' }}</small>
                </div>
                <div class="detail-record-field detail-record-field-cyan">
                    <span>Metode Pembayaran</span>
                    @if($batch->getEffectivePaymentMethod())
                        <strong>{{ $batch->getEffectivePaymentMethod()->name }}</strong>
                        <small>{{ $batch->getEffectivePaymentMethod()->type === 'qris' ? 'QRIS' : ucfirst($batch->getEffectivePaymentMethod()->type) }} · Global</small>
                    @elseif($batch->qris_image_path)
                        <strong>Legacy QRIS</strong>
                        <small>Menggunakan QRIS lama per batch</small>
                    @else
                        <strong>Belum diatur</strong>
                        <small>Checkout pelanggan tidak dapat digunakan</small>
                    @endif
                </div>
            </div>

            <div class="order-table-scroll mt-4">
                <table class="order-table responsive-card-table">
                    <thead><tr><th>Variasi</th><th>Harga DP</th><th>Harga Lunas</th><th>Ketersediaan</th></tr></thead>
                    <tbody>
                        @forelse($batch->products as $product)
                            <tr><td data-label="Variasi"><div class="order-table-primary">{{ $product->variant ?: $product->name }}</div></td><td data-label="Harga DP">Rp {{ number_format($product->pivot->dp_price, 0, ',', '.') }}</td><td data-label="Harga lunas">Rp {{ number_format($product->pivot->full_price, 0, ',', '.') }}</td><td data-label="Ketersediaan">{{ $product->pivot->is_available ? 'Tersedia' : 'Tidak tersedia' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">Belum ada variasi katalog.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="detail-record-section">
            <div class="detail-record-section-heading">
                <div>
                    <h3>Informasi Batch</h3>
                    <p>Batch menjadi pusat progress untuk seluruh pesanan di dalamnya.</p>
                </div>
            </div>
            <div class="detail-record-field detail-record-field-plain">
                <span>Catatan batch</span>
                <p>{{ $batch->notes ?: ($batch->description ?: 'Belum ada catatan batch.') }}</p>
            </div>
        </section>

        <section class="detail-record-section detail-record-section-tinted">
            <div class="detail-record-section-heading">
                <div>
                    <h3>Progress Batch</h3>
                    <p>Status saat ini: {{ $batch->currentStatus?->name ?: 'Belum ditentukan' }}. Pesanan tanpa status khusus otomatis mengikuti progress ini.</p>
                </div>
                @unless($batch->progress_locked || $batch->is_archived)
                    <button type="button" class="admin-form-inline-action" data-status-modal-open="batch-progress">Perbarui Progress</button>
                @endunless
            </div>

            @if($batch->statusHistories->isNotEmpty())
                <div class="order-special-status-list mt-0">
                    <div class="order-special-status-list-heading">
                        <div>
                            <h4>Riwayat Progress Batch</h4>
                            <p>Daftar perubahan status dari yang terbaru.</p>
                        </div>
                        <span>{{ $batch->statusHistories->count() }} perubahan</span>
                    </div>
                    @foreach($batch->statusHistories as $history)
                        <div class="order-special-status-item">
                            <div>
                                <x-status-badge :status="$history->newStatus" />
                                <p>
                                    {{ $history->oldStatus ? 'Dari '.$history->oldStatus->name.'. ' : 'Status awal batch. ' }}
                                    {{ $history->note ?: 'Tanpa catatan perubahan.' }} · {{ $history->changedBy?->name ?: 'Sistem' }}
                                </p>
                            </div>
                            <span>{{ $history->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="order-special-status-empty">
                    <p>Belum ada riwayat perubahan progress untuk batch ini.</p>
                </div>
            @endif

            @if($batch->is_archived)
                <div class="admin-form-lock-notice mt-3">
                    <strong>Batch telah diarsipkan</strong>
                    <p>Data ini hanya tersedia sebagai referensi dan tidak dapat diedit atau diperbarui lagi.</p>
                </div>
            @elseif($batch->progress_locked)
                <div class="admin-form-lock-notice mt-3">
                    <strong>Progress batch telah selesai</strong>
                    <p>Status {{ $batch->currentStatus?->name }} bersifat final sehingga progress tidak dapat diperbarui lagi.</p>
                </div>
            @endif
        </section>

        <section class="detail-record-section detail-record-table-section">
            <div class="detail-record-section-heading detail-record-table-heading">
                <div>
                    <h3>Pesanan dalam Batch</h3>
                    <p>Daftar ini terbentuk dari pesanan yang memilih batch {{ $batch->batch_number }}, bukan dari penambahan member secara terpisah.</p>
                </div>
            </div>
            <div class="order-table-scroll">
                <table class="order-table responsive-card-table">
                    <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Item</th><th>Pembayaran</th><th>Status efektif</th><th>Update terakhir</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @forelse($batch->orders as $order)
                            <tr>
                                <td data-label="Pesanan"><div class="order-table-primary text-violet-700">{{ $order->order_code }}</div></td>
                                <td data-label="Pelanggan"><div class="order-table-primary">{{ $order->member->display_name }}</div><div class="order-table-secondary">Username: {{ $order->member->username }}</div></td>
                                <td data-label="Item" class="font-semibold text-zinc-700">{{ $order->items->sum('quantity') }}</td>
                                <td data-label="Pembayaran"><div class="order-table-primary">{{ $order->payment_type_label }}</div><div class="order-table-secondary">{{ $order->payment_amount ? 'Rp '.number_format($order->payment_amount, 0, ',', '.') : 'Belum ada pembayaran' }}</div></td>
                                <td data-label="Status efektif"><x-status-badge :status="$order->effective_status" /></td>
                                <td data-label="Update terakhir"><div class="text-zinc-700">{{ $order->updated_at->format('d M Y') }}</div><div class="order-table-secondary">{{ $order->updated_at->format('H:i') }}</div></td>
                                <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.member-orders.show', $order, false) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-500">Belum ada pesanan yang menggunakan batch ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </article>

    @unless($batch->progress_locked || $batch->is_archived)
    <div class="status-modal" data-status-modal="batch-progress" role="dialog" aria-modal="true" aria-labelledby="batch-progress-modal-title" aria-hidden="true" hidden>
        <button type="button" class="status-modal-backdrop" data-status-modal-close aria-label="Tutup modal"></button>
        <div class="status-modal-surface order-status-modal-surface" tabindex="-1">
            <header class="status-modal-header">
                <div>
                    <p>Progress Batch</p>
                    <h2 id="batch-progress-modal-title">Perbarui {{ $batch->batch_number }}</h2>
                </div>
                <button type="button" class="status-modal-close" data-status-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>

            <div class="status-modal-body">
                <form method="POST" action="{{ route('admin.batches.status', $batch, false) }}" class="order-status-override-form">
                    @csrf
                    <label>
                        <span>Status baru</span>
                        <select name="status_id" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}" @selected($batch->current_status_id === $status->id)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span>Catatan perubahan</span>
                        <textarea name="note" rows="3" maxlength="1000" placeholder="Opsional, jelaskan perubahan progress"></textarea>
                    </label>
                    <div class="flex justify-end">
                        <button class="admin-form-primary" type="submit">Simpan Progress Batch</button>
                    </div>
                </form>
            </div>

            <footer class="status-detail-footer status-modal-footer">
                <p>Setiap perubahan disimpan dalam riwayat agar progress batch dapat ditelusuri.</p>
                <button type="button" class="admin-form-secondary" data-status-modal-close>Tutup</button>
            </footer>
        </div>
    </div>
    @endunless
</x-layouts.app>
