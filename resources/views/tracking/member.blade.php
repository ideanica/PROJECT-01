<x-layouts.app title="Rekap {{ $member->display_name }}">
    <main class="page customer-detail-page"><div class="page-mid">
    <div class="detail-topbar"><a class="top-back" href="{{ route('tracking.index') }}" aria-label="Kembali"><x-public-icon name="arrow-left" :size="18" /></a><strong>Riwayat Pembelian</strong></div>
    <header class="customer-detail-heading"><span>RIWAYAT MEMBER</span><h1>{{ $member->display_name }}</h1><p>{{ $member->orders->count() }} pembelian ditemukan</p></header>

    <div class="order-table-card customer-tracking-card">
        <div class="order-table-scroll">
            <table class="order-table tracking-history-table">
                <thead>
                    <tr>
                        <th>Pesanan & Batch</th>
                        <th>Status</th>
                        <th>Jumlah item</th>
                        <th>Pajak/EMS</th>
                        <th>Update terakhir</th>
                        <th><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($member->orders as $order)
                        <tr class="transition hover:bg-zinc-50/80">
                            <td data-label="Pesanan">
                                <div class="order-table-primary font-mono text-violet-700">{{ $order->order_code }}</div>
                                <div class="order-table-secondary">{{ $order->batch->batch_number }} · {{ $order->batch->batch_name }}</div>
                            </td>
                            <td data-label="Status"><x-status-badge :status="$order->tracking_status" /></td>
                            <td data-label="Jumlah item" class="font-semibold text-zinc-700">{{ $order->items->sum('quantity') }}</td>
                            <td data-label="Pajak/EMS">
                                @if($order->ems_tax_is_published)
                                    <div class="order-table-primary">Rp {{ number_format($order->ems_tax_amount, 0, ',', '.') }}</div>
                                    <div class="order-table-secondary">{{ $order->ems_tax_status_label }}</div>
                                @else
                                    <span class="text-zinc-400">Belum ada</span>
                                @endif
                            </td>
                            <td data-label="Update terakhir" class="text-zinc-500">{{ $order->updated_at->format('d M Y H:i') }}</td>
                            <td data-label="Aksi" class="text-right">
                                <a class="order-table-action" href="{{ URL::temporarySignedRoute('tracking.order', now()->addMinutes(15), ['memberCode' => $member->member_code, 'memberOrder' => $order]) }}">Lihat detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr class="tracking-table-empty"><td colspan="6" class="px-4 py-8 text-center text-zinc-500">Belum ada pembelian yang tercatat untuk username ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div></main>
</x-layouts.app>

