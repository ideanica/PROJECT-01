<x-layouts.app title="Kelola Status">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Kelola Status" description="Susun tahapan progress batch, pesanan pelanggan, item produk, dan pembayaran.">
            <x-slot:action><a class="admin-primary-action hover:bg-[#275a3a]!" href="{{ route('admin.order-statuses.create', [], false) }}">+ Tambah status</a></x-slot:action>
        </x-page-heading>
    </div>

    <section class="admin-status-flow relative mb-6 rounded-2xl border border-[#cbdfd3] bg-gradient-to-r from-[#eef4ef] via-[#f4f9f5] to-white p-4 shadow-[0_20px_45px_-34px_rgba(39,90,58,0.6)] sm:p-5">
        <div class="flex items-center gap-2.5">
            <div class="text-sm font-bold tracking-tight text-[#275a3a]">Alur utama batch pembelian</div>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-semibold text-[#275a3a]">
            @foreach(['Menunggu Pemesanan', 'Sudah Dipesan', 'Tiba di Gudang Luar Negeri', 'Dikirim ke Indonesia', 'Tiba di Gudang Admin', 'Siap Distribusi', 'Selesai'] as $step)
                <span class="rounded-full border border-[#c3d9ca] bg-white px-3 py-1.5 shadow-[0_1px_2px_rgba(39,90,58,0.08)]">{{ $step }}</span>
                @if(!$loop->last)<span class="flex shrink-0 items-center text-[#2f6b46]" aria-hidden="true"><svg viewBox="0 0 24 24" class="h-2.5 w-2.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h13M13 6l6 6-6 6"/></svg></span>@endif
            @endforeach
        </div>
        <p class="mt-4 border-t border-[#d7e6dc] pt-3 text-sm leading-relaxed text-[#4a6558]">Status batch menjadi status dasar seluruh pesanan di dalamnya. Status pesanan dan item hanya dipakai jika ada kondisi khusus yang berbeda dari batch.</p>
    </section>

    @php
        $scopeDefinitions = \App\Models\OrderStatus::scopeDefinitions();
        $groupedStatuses = $statuses->groupBy('scope');
        $requestedScope = request('scope');
        $activeScope = is_string($requestedScope) && array_key_exists($requestedScope, $scopeDefinitions) ? $requestedScope : 'batch';
    @endphp

    <div class="status-manager-card status-folder-map before:bg-none!" data-status-folder-map>
        <form class="border-b border-zinc-200 px-4 py-3 text-[12px] sm:px-5" data-auto-filter>
            <input type="hidden" name="scope" value="{{ $activeScope }}" data-status-folder-input>
            <div class="flex items-center gap-1.5 overflow-x-auto">
                <label class="flex h-9 w-[24rem] shrink-0 items-center gap-2 rounded-md border border-zinc-200 bg-white px-9 shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition focus-within:border-[#2f6b46]/45">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-zinc-700" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    <input class="h-full w-full border-0! bg-transparent! p-0! text-zinc-700 shadow-none! outline-none! focus:shadow-none!" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode status...">
                </label>

                <select class="h-8 w-auto shrink-0 cursor-pointer rounded-xl border-zinc-200! bg-white! px-1.5! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]!" name="active">
                    <option value="">Aktif & nonaktif</option>
                    <option value="1" @selected(request('active') === '1')>Aktif saja</option>
                    <option value="0" @selected(request('active') === '0')>Nonaktif saja</option>
                </select>

                <button class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 font-semibold text-[#27272a] shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition hover:bg-[#f4f4f5] focus:outline-none focus:ring-4 focus:ring-zinc-900/5" type="submit">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    Cari
                </button>
            </div>
        </form>

        <div class="status-folder-tabs max-[820px]:flex! max-[820px]:overflow-x-auto! max-[820px]:[&_.status-folder-tab]:min-w-max! max-[820px]:[&_.status-folder-tab]:shrink-0! max-[820px]:[&_.status-folder-tab]:justify-center! [&_.status-folder-tab:hover]:bg-[#eef4ef]! [&_.status-folder-tab:hover]:text-[#275a3a]! [&_.status-folder-tab.is-active]:text-[#275a3a]! [&_.status-folder-tab.is-active::after]:bg-[#2f6b46]! [&_.status-folder-tab.is-active_small]:bg-[#eef4ef]! [&_.status-folder-tab.is-active_small]:text-[#275a3a]!" role="tablist" aria-label="Kategori status">
            @foreach($scopeDefinitions as $scope => $definition)
                <button
                    type="button"
                    role="tab"
                    class="status-folder-tab {{ $activeScope === $scope ? 'is-active' : '' }}"
                    data-status-folder-tab="{{ $scope }}"
                    aria-controls="status-folder-{{ $scope }}"
                    aria-selected="{{ $activeScope === $scope ? 'true' : 'false' }}"
                    tabindex="{{ $activeScope === $scope ? '0' : '-1' }}"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="fill-none! stroke-current" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        @switch($scope)
                            @case('batch')<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5"/><path d="m3 17 9 5 9-5"/>@break
                            @case('member_order')<circle cx="12" cy="8" r="3.75"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>@break
                            @case('order_item')<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="M4 7.5 12 12l8-4.5"/><path d="M12 12v9"/>@break
                            @case('payment')<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3 10h18M7 15h4"/>@break
                            @default<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>
                        @endswitch
                    </svg>
                    <span>{{ $definition['label'] }}</span>
                    <small>{{ $groupedStatuses->get($scope, collect())->count() }}</small>
                </button>
            @endforeach
        </div>

        <div class="status-folder-content">
            @foreach($scopeDefinitions as $scope => $definition)
                @php($scopeStatuses = $groupedStatuses->get($scope, collect()))
                <section
                    id="status-folder-{{ $scope }}"
                    role="tabpanel"
                    class="status-folder-panel"
                    data-status-folder-panel="{{ $scope }}"
                    @if($activeScope !== $scope) hidden @endif
                >
                    <div class="status-folder-heading">
                        <div>
                            <h2>{{ $definition['title'] }}</h2>
                            <p>{{ $definition['description'] }}</p>
                        </div>
                        <span class="bg-[#eef4ef]! text-[#275a3a]!">{{ $scopeStatuses->count() }} status</span>
                    </div>

                    <div class="status-folder-application border-[#e4e4e7]! bg-[#fafafa]!">
                        <span class="status-folder-application-icon bg-[#eef4ef]! text-[#2f6b46]!" aria-hidden="true">
                            <svg viewBox="0 0 20 20"><circle cx="10" cy="10" r="7.5"/><path d="M10 8.5v5M10 6.25h.01"/></svg>
                        </span>
                        <div>
                            <strong>Diterapkan pada: {{ $definition['applies_to'] }}</strong>
                            <p>{{ $definition['application'] }}</p>
                        </div>
                    </div>

                    <div class="status-table-frame">
                        <div class="order-table-scroll">
                        <table class="order-table status-table responsive-card-table">
                            <colgroup>
                                <col class="status-table-col-number">
                                <col class="status-table-col-status">
                                <col class="status-table-col-description">
                                <col class="status-table-col-properties">
                                <col class="status-table-col-usage">
                                <col class="status-table-col-actions">
                            </colgroup>
                            <thead><tr><th class="min-[821px]:text-center!">No.</th><th>Status</th><th>Keterangan</th><th>Sifat</th><th class="min-[821px]:text-center!">Dipakai</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse($scopeStatuses as $status)
                                    @php($usage = $status->batches_count + $status->member_orders_count + $status->order_items_count + $status->payment_member_orders_count + $status->old_histories_count + $status->new_histories_count)
                                    <tr class="{{ $status->is_active ? '' : 'bg-zinc-50 opacity-60' }}">
                                        <td data-label="No." class="min-[821px]:text-center!"><span class="min-[821px]:mx-auto grid h-7 w-7 place-items-center rounded-md bg-zinc-100">{{ $loop->iteration }}</span></td>
                                        <td data-label="Status"><x-status-badge :status="$status" /></td>
                                        <td data-label="Keterangan" class="max-w-md text-zinc-600">{{ $status->description ?: 'Belum ada keterangan untuk pelanggan.' }}</td>
                                        <td data-label="Sifat">
                                            <div class="flex flex-wrap gap-1">
                                                @if($status->is_initial)<span class="rounded bg-blue-50 px-2 py-1 text-xs text-blue-700">Awal</span>@endif
                                                @if($status->is_final)<span class="rounded bg-emerald-50 px-2 py-1 text-xs text-emerald-700">Final</span>@endif
                                                @if($status->locks_order_editing)<span class="rounded bg-amber-50 px-2 py-1 text-xs text-amber-700">Kunci edit pesanan</span>@endif
                                                <span class="rounded bg-zinc-100 px-2 py-1 text-xs">{{ $status->status_type }}</span>
                                                @unless($status->is_active)<span class="rounded bg-red-50 px-2 py-1 text-xs text-red-700">Nonaktif</span>@endunless
                                            </div>
                                        </td>
                                        <td data-label="Dipakai" class="min-[821px]:text-center!">{{ $usage }}×</td>
                                        <td data-card-action class="text-right">
                                            <div class="status-table-actions">
                                                <a class="order-table-action" href="{{ route('admin.order-statuses.show', $status, false) }}">Detail</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-12 text-center text-zinc-400">Tidak ada status pada folder {{ strtolower($definition['label']) }} yang cocok dengan pencarian atau filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>

</x-layouts.app>
