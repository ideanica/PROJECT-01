<x-layouts.app title="Batch">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Batch" description="Kelola periode pembelian dan pantau progres setiap batch.">
            <x-slot:action>
                <div class="flex flex-wrap gap-2">
                    <a class="admin-primary-action hover:bg-[#275a3a]!" href="{{ route('admin.batches.create', [], false) }}">+ Tambah batch</a>
                </div>
            </x-slot:action>
        </x-page-heading>
    </div>

    <div class="order-table-card before:bg-none!">
        <form class="border-b border-zinc-200 px-4 py-3 text-[12px] sm:px-5" data-auto-filter>
            <input type="hidden" name="view" value="{{ $archiveView }}">
            <div class="flex flex-wrap items-center gap-1.5 [&_.select2-container]:w-full! lg:[&_.select2-container]:w-auto!">
                <label class="flex h-9 w-full items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition focus-within:border-[#2f6b46]/45 focus-within:shadow-[0_0_0_4px_rgba(47,107,70,0.12)] lg:w-[18rem]">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-zinc-700" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    <input class="h-full w-full border-0! bg-transparent! p-0! text-zinc-700 shadow-none! outline-none! focus:shadow-none!" name="q" value="{{ request('q') }}" placeholder="Cari nomor atau nama batch...">
                </label>

                <select class="h-8 w-full cursor-pointer rounded-xl border-zinc-200! bg-white! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]! lg:w-auto" name="status_id">
                    <option value="">Semua status</option>
                    @foreach($statuses as $status)<option value="{{ $status->id }}" @selected(request('status_id') == $status->id)>{{ $status->name }}</option>@endforeach
                </select>

                <div class="flex h-9 w-full items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2 shadow-[0_1px_2px_rgba(24,24,27,0.05)] lg:w-auto">
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_from" type="date" value="{{ request('date_from') }}" aria-label="Tanggal batch mulai">
                    <span class="text-zinc-300">—</span>
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_to" type="date" value="{{ request('date_to') }}" aria-label="Tanggal batch akhir">
                </div>

                <button class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 font-semibold text-[#27272a] shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition hover:bg-[#f4f4f5] focus:outline-none focus:ring-4 focus:ring-zinc-900/5 lg:w-auto" type="submit">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    Cari
                </button>
            </div>
        </form>

        <div class="status-folder-tabs [&_.status-folder-tab:hover]:bg-[#eef4ef]! [&_.status-folder-tab:hover]:text-[#275a3a]! [&_.status-folder-tab.is-active]:text-[#275a3a]! [&_.status-folder-tab.is-active::after]:bg-[#2f6b46]! [&_.status-folder-tab.is-active_small]:bg-[#eef4ef]! [&_.status-folder-tab.is-active_small]:text-[#275a3a]!" role="tablist" aria-label="Kategori batch">
            @foreach([
                'active' => ['label' => 'Batch Aktif', 'count' => $batchCounts['active']],
                'archived' => ['label' => 'Arsip', 'count' => $batchCounts['archived']],
            ] as $view => $tab)
                <a
                    href="{{ route('admin.batches.index', ['view' => $view], false) }}"
                    role="tab"
                    class="status-folder-tab {{ $archiveView === $view ? 'is-active' : '' }}"
                    aria-selected="{{ $archiveView === $view ? 'true' : 'false' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="fill-none! stroke-current" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        @if($view === 'archived')
                            <rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>
                        @else
                            <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>
                        @endif
                    </svg>
                    <span>{{ $tab['label'] }}</span>
                    <small>{{ $tab['count'] }}</small>
                </a>
            @endforeach
            <a
                href="{{ route('admin.batches.settings', [], false) }}"
                role="tab"
                class="status-folder-tab"
                aria-selected="false"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true" class="fill-none! stroke-current" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Pengaturan</span>
            </a>
        </div>

        <div class="status-folder-content">
            <section class="status-folder-panel">
                <div class="status-folder-heading">
                    <div>
                        <h2>{{ $archiveView === 'archived' ? 'Batch yang Diarsipkan' : 'Batch Operasional Aktif' }}</h2>
                        <p>{{ $archiveView === 'archived' ? 'Batch yang tidak lagi tampil pada daftar operasional utama.' : 'Batch yang masih tersedia untuk kebutuhan operasional dan pembuatan pesanan.' }}</p>
                    </div>
                    <span class="bg-[#eef4ef]! text-[#275a3a]!">{{ $batches->total() }} batch</span>
                </div>

                <div class="status-folder-application border-[#e4e4e7]! bg-[#fafafa]!">
                    <span class="status-folder-application-icon bg-[#eef4ef]! text-[#2f6b46]!" aria-hidden="true">
                        <svg viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/><path d="M10 8.5v5M10 6.25h.01"/></svg>
                    </span>
                    <div>
                        <strong>{{ $archiveView === 'archived' ? 'Folder arsip batch' : 'Folder batch aktif' }}</strong>
                        <p>{{ $archiveView === 'archived' ? 'Data di sini dipisahkan dari daftar utama dan hanya tersedia sebagai referensi readonly.' : 'Batch yang diarsipkan tidak akan muncul pada folder ini.' }}</p>
                    </div>
                </div>

                <div class="status-table-frame">
                    <div class="order-table-scroll">
                        <table class="order-table responsive-card-table [&_tbody_tr:hover]:bg-[#fafafa]!">
                            <thead><tr><th>Batch</th><th>Status</th><th>Pesanan</th><th>Item</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse ($batches as $batch)
                                    <tr>
                                        <td data-label="Batch"><div class="order-table-primary">{{ $batch->batch_number }}</div><div class="order-table-secondary">{{ $batch->batch_name ?: 'Tanpa nama batch' }}</div></td>
                                        <td data-label="Status"><x-status-badge :status="$batch->currentStatus" /></td>
                                        <td data-label="Pesanan" class="text-center text-zinc-600">{{ $batch->orders_count }}</td>
                                        <td data-label="Item" class="text-center text-zinc-600">{{ $batch->items_count }}</td>
                                        <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.batches.show', $batch, false) }}">Detail</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-12 text-center text-zinc-400">{{ $archiveView === 'archived' ? 'Belum ada batch yang diarsipkan.' : 'Tidak ada batch aktif yang cocok dengan pencarian atau filter.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="order-table-footer [&_span[aria-current='page']_span]:border-[#d4d4d8]! [&_span[aria-current='page']_span]:bg-[#f4f4f5]! [&_span[aria-current='page']_span]:text-[#3f3f46]!"><span>Menampilkan {{ $batches->firstItem() ?? 0 }}–{{ $batches->lastItem() ?? 0 }} dari {{ $batches->total() }} batch</span><div>{{ $batches->links() }}</div></div>
            </section>
        </div>
    </div>
</x-layouts.app>
