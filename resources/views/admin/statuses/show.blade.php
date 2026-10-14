@php
    $scopeDefinitions = \App\Models\OrderStatus::scopeDefinitions();
    $typeLabels = [
        'process' => 'Sedang diproses',
        'success' => 'Berhasil / selesai',
        'failed' => 'Bermasalah',
        'cancelled' => 'Dibatalkan',
    ];
    $usageItems = [
        ['label' => 'Batch aktif', 'value' => $orderStatus->batches_count],
        ['label' => 'Status khusus pesanan', 'value' => $orderStatus->member_orders_count],
        ['label' => 'Status khusus item', 'value' => $orderStatus->order_items_count],
        ['label' => 'Status pembayaran', 'value' => $orderStatus->payment_member_orders_count],
        ['label' => 'Riwayat sebagai status lama', 'value' => $orderStatus->old_histories_count],
        ['label' => 'Riwayat sebagai status baru', 'value' => $orderStatus->new_histories_count],
    ];
    $totalUsage = collect($usageItems)->sum('value');
    $scopeDefinition = $scopeDefinitions[$orderStatus->scope] ?? null;
@endphp

<x-layouts.app title="Status {{ $orderStatus->name }}">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Detail Status" description="Informasi, aturan, cakupan, dan pemakaian status dalam sistem.">
            <x-slot:action>
                <div class="flex flex-wrap gap-2">
                    <a class="admin-form-primary" href="{{ route('admin.order-statuses.edit', $orderStatus, false) }}">Edit Status</a>
                    @if($orderStatus->is_active || ! $totalUsage)
                        <form method="POST" action="{{ route('admin.order-statuses.destroy', $orderStatus, false) }}" onsubmit="return confirm('{{ $totalUsage ? 'Nonaktifkan status ini?' : 'Hapus permanen status ini?' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-form-danger-action">{{ $totalUsage ? 'Nonaktifkan Status' : 'Hapus Status' }}</button>
                        </form>
                    @endif
                </div>
            </x-slot:action>
        </x-page-heading>
    </div>

    <article class="detail-record-card before:bg-none! [&_.detail-record-hero]:bg-[linear-gradient(135deg,#ffffff,#f6faf6_58%,#eef6ef)]!">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker text-[#275a3a]!">Manajemen Status</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="detail-record-title">{{ $orderStatus->name }}</h2>
                    {{-- <x-status-badge :status="$orderStatus" /> --}}
                    <span class="detail-record-state {{ $orderStatus->is_active ? 'detail-record-state-active' : 'detail-record-state-muted' }}">{{ $orderStatus->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <p class="detail-record-description">{{ $orderStatus->description ?: 'Belum ada keterangan untuk status ini.' }}</p>
            </div>
            <span class="detail-record-id">ID #{{ str_pad($orderStatus->id, 4, '0', STR_PAD_LEFT) }}</span>
        </header>

        <section class="detail-record-section">
            <div class="detail-record-summary sm:grid-cols-2 xl:grid-cols-4">
                <div class="detail-record-field detail-record-field-violet"><span>Kode sistem</span><strong>{{ $orderStatus->code }}</strong></div>
                <div class="detail-record-field detail-record-field-blue"><span>Diterapkan pada</span><strong>{{ $scopeDefinition['label'] ?? $orderStatus->scope }}</strong></div>
                <div class="detail-record-field detail-record-field-cyan"><span>Total pemakaian</span><strong>{{ $totalUsage }}×</strong></div>
                <div class="detail-record-field detail-record-field-amber"><span>Jenis hasil</span><strong>{{ $typeLabels[$orderStatus->status_type] ?? $orderStatus->status_type }}</strong></div>
            </div>
        </section>

        <section class="detail-record-section detail-record-section-tinted">
            <div class="detail-record-section-heading">
                <div><h3>Penerapan Status</h3><p>{{ $scopeDefinition['description'] ?? 'Cakupan penerapan status.' }}</p></div>
            </div>
            <div class="status-folder-application border-[#e4e4e7]! bg-[#eef4ef]! mb-0">
                <span class="status-folder-application-icon bg-[#eef4ef]! text-[#2f6b46]!" aria-hidden="true">
                    <svg viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/><path d="M10 8.5v5M10 6.25h.01"/></svg>
                </span>
                <div>
                    <strong>Diterapkan pada: {{ $scopeDefinition['applies_to'] ?? $orderStatus->scope }}</strong>
                    <p>{{ $scopeDefinition['application'] ?? '' }}</p>
                </div>
            </div>
        </section>

        <section class="detail-record-section">
            <div class="detail-record-section-heading">
                <div><h3>Perilaku Status</h3><p>Aturan yang aktif ketika status digunakan.</p></div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($orderStatus->is_initial)<span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Status awal</span>@endif
                @if($orderStatus->is_final)<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Status final</span>@endif
                @if($orderStatus->locks_order_editing)<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Mengunci perubahan pesanan</span>@endif
                @unless($orderStatus->is_initial || $orderStatus->is_final || $orderStatus->locks_order_editing)<span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600">Status proses biasa</span>@endunless
            </div>
        </section>

        <section class="detail-record-section detail-record-section-tinted">
            <div class="detail-record-section-heading">
                <div><h3>Rincian Pemakaian</h3><p>{{ $totalUsage }} pemakaian tercatat pada data aktif dan riwayat.</p></div>
            </div>
            <div class="status-detail-usage">
                @foreach($usageItems as $item)
                    <div class="status-detail-usage-item"><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}×</strong></div>
                @endforeach
            </div>
        </section>

    </article>
</x-layouts.app>
