<x-layouts.app title="Pesanan">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Pesanan" description="Kelola pembelian pelanggan dan status produk di setiap batch.">
            <x-slot:action><a class="admin-primary-action hover:bg-[#275a3a]!" href="{{ route('admin.member-orders.create', [], false) }}">+ Tambah pesanan</a></x-slot:action>
        </x-page-heading>
    </div>

    <div class="order-table-card before:bg-none!">
        <form class="border-b border-zinc-200 px-4 py-3 text-[12px] sm:px-5" data-auto-filter>
            <div class="flex flex-wrap items-center gap-1.5">
                <label class="flex h-9 w-full items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition focus-within:border-[#2f6b46]/45 focus-within:shadow-[0_0_0_4px_rgba(47,107,70,0.12)] lg:w-[18rem]">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-zinc-700" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    <input class="h-full w-full border-0! bg-transparent! p-0! text-zinc-700 shadow-none! outline-none! focus:shadow-none!" name="q" value="{{ request('q') }}" placeholder="Cari kode pesanan, pelanggan, atau batch...">
                </label>

                <div class="flex h-9 w-full items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2 shadow-[0_1px_2px_rgba(24,24,27,0.05)] lg:w-auto">
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_from" type="date" value="{{ request('date_from') }}" aria-label="Tanggal pesanan mulai">
                    <span class="text-zinc-300">—</span>
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_to" type="date" value="{{ request('date_to') }}" aria-label="Tanggal pesanan akhir">
                </div>

                <button class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 font-semibold text-[#27272a] shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition hover:bg-[#f4f4f5] focus:outline-none focus:ring-4 focus:ring-zinc-900/5 lg:w-auto" type="submit">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>Cari
                </button>
            </div>
        </form>
        @if (request()->boolean('problem'))
            <div class="order-table-filter-notice">
                <span>Menampilkan pesanan bermasalah — status gagal atau batal.</span>
                <a href="{{ route('admin.member-orders.index') }}">Tampilkan semua</a>
            </div>
        @endif
        <div class="order-table-scroll">
            <table class="order-table responsive-card-table [&_tbody_tr:hover]:bg-[#fafafa]!">
                <thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Batch</th><th>Status</th><th>Item</th><th>Total</th><th>Pajak / EMS</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td data-label="Pesanan"><div class="order-table-primary text-black!">{{ $order->order_code }}</div><div class="order-table-secondary">Diperbarui {{ $order->updated_at->format('d M Y, H:i') }}</div></td>
                            <td data-label="Pelanggan"><div class="order-table-primary">{{ $order->member->display_name }}</div><div class="order-table-secondary">{{ $order->member->username ?: '-' }}</div></td>
                            <td data-label="Batch" class="text-zinc-600">{{ $order->batch->batch_number }}</td>
                            <td data-label="Status"><x-status-badge :status="$order->effective_status" /></td>
                            <td data-label="Item" class="font-semibold text-zinc-700">{{ $order->items_count }}</td>
                            <td data-label="Total" class="whitespace-nowrap! font-semibold text-zinc-700">{{ $order->total_amount ? 'Rp '.number_format($order->total_amount, 0, ',', '.') : '-' }}</td>
                            <td data-label="Pajak / EMS">
                                @if($order->has_ems_tax_bill)
                                    <div class="order-table-primary">Rp {{ number_format((float) $order->ems_tax_amount, 0, ',', '.') }}</div>
                                    <div class="order-table-secondary">{{ $order->ems_tax_status_label }}</div>
                                @else
                                    <span class="text-xs text-zinc-400">Belum ditagihkan</span>
                                @endif
                            </td>
                            <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.member-orders.show', $order, false) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-zinc-400">Belum ada pesanan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer [&_span[aria-current='page']_span]:border-[#d4d4d8]! [&_span[aria-current='page']_span]:bg-[#f4f4f5]! [&_span[aria-current='page']_span]:text-[#3f3f46]!"><span>Menampilkan {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} pesanan</span><div>{{ $orders->links() }}</div></div>
    </div>
</x-layouts.app>
