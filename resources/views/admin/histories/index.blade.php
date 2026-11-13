<x-layouts.app title="Riwayat Status">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Riwayat Status" description="Audit trail perubahan status batch, pesanan, dan item produk." />
    </div>

    <div class="order-table-card before:bg-none!">
        <form class="border-b border-zinc-200 px-4 py-3 text-[12px] sm:px-5" data-auto-filter>
            <div class="flex flex-wrap items-center gap-1.5 [&_.select2-container]:w-full! lg:[&_.select2-container]:w-auto!">
                <label class="flex h-9 w-full items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition focus-within:border-[#2f6b46]/45 focus-within:shadow-[0_0_0_4px_rgba(47,107,70,0.12)] lg:w-[18rem]">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-zinc-700" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    <input class="h-full w-full border-0! bg-transparent! p-0! text-zinc-700 shadow-none! outline-none! focus:shadow-none!" name="q" value="{{ request('q') }}" placeholder="Cari batch, pesanan, status, atau catatan...">
                </label>

                <select class="h-8 w-full cursor-pointer rounded-xl border-zinc-200! bg-white! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]! lg:w-auto" name="trackable_type" aria-label="Filter objek">
                    <option value="">Semua objek</option>
                    @foreach($trackableTypes as $key => $trackable)
                        <option value="{{ $key }}" @selected(request('trackable_type') === $key)>{{ $trackable['label'] }}</option>
                    @endforeach
                </select>

                <div class="flex h-9 w-full items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2 shadow-[0_1px_2px_rgba(24,24,27,0.05)] lg:w-auto">
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_from" type="date" value="{{ request('date_from') }}" aria-label="Tanggal mulai">
                    <span class="text-zinc-300">—</span>
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_to" type="date" value="{{ request('date_to') }}" aria-label="Tanggal akhir">
                </div>

                <button class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-[14px] border border-zinc-200 bg-white px-4 font-semibold text-[#27272a] shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition hover:bg-[#f4f4f5] focus:outline-none focus:ring-4 focus:ring-zinc-900/5 lg:w-auto" type="submit">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    Cari
                </button>
            </div>
        </form>

        <div class="order-table-scroll">
            <table class="order-table responsive-card-table [&_tbody_tr:hover]:bg-[#fafafa]!">
                <thead><tr><th>Objek</th><th>Status lama</th><th>Status baru</th><th>Catatan</th><th>Admin</th><th>Waktu</th></tr></thead>
                <tbody>
                    @forelse($histories as $history)
                        <tr>
                            <td data-label="Objek"><div class="order-table-primary">{{ $history->trackableLabel() }}</div><div class="order-table-secondary">#{{ $history->trackable_id }}</div></td>
                            <td data-label="Status lama"><x-status-badge :status="$history->oldStatus" /></td>
                            <td data-label="Status baru"><x-status-badge :status="$history->newStatus" /></td>
                            <td data-label="Catatan" class="max-w-xs text-zinc-600">{{ $history->note ?: '-' }}</td>
                            <td data-label="Admin" class="text-zinc-600">{{ $history->changedBy?->name ?: 'Sistem' }}</td>
                            <td data-label="Waktu" class="whitespace-nowrap"><div class="text-zinc-700">{{ $history->created_at->format('d M Y') }}</div><div class="order-table-secondary">{{ $history->created_at->format('H:i') }}</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-12 text-center text-zinc-400">Belum ada riwayat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer"><span>Menampilkan {{ $histories->firstItem() ?? 0 }}–{{ $histories->lastItem() ?? 0 }} dari {{ $histories->total() }} aktivitas</span><div>{{ $histories->links('admin.partials.pagination') }}</div></div>
    </div>
</x-layouts.app>
