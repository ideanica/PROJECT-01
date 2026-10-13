<x-layouts.app title="Kelola Status">
    <x-page-heading title="Kelola Status" description="Kelola status tracking perjalanan, status pembayaran. Label group dikelola di Kelola Customer dan Group.">
        <x-slot:action><a class="admin-primary-action" href="{{ route('admin.order-statuses.create', [], false) }}">+ Tambah status</a></x-slot:action>
    </x-page-heading>

    <section class="admin-status-flow mb-6 rounded-2xl border border-violet-100 bg-gradient-to-r from-violet-50 via-blue-50 to-cyan-50 p-5">
        <div class="text-sm font-bold text-violet-800">Alur utama batch pembelian</div>
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold text-violet-900">
            @foreach(['Menunggu Pemesanan', 'Sudah Dipesan', 'Tiba di Gudang Luar Negeri', 'Dikirim ke Indonesia', 'Tiba di Gudang Admin', 'Siap Distribusi', 'Selesai'] as $step)
                <span class="rounded-full border border-violet-200 bg-white px-3 py-1.5">{{ $step }}</span>
                @if(!$loop->last)<span class="text-violet-400">→</span>@endif
            @endforeach
        </div>
        <p class="mt-3 text-sm text-violet-800">Status batch menjadi status dasar seluruh pesanan di dalamnya. Status pesanan dan item hanya dipakai jika ada kondisi khusus yang berbeda dari batch.</p>
    </section>

    @php
        $scopeDefinitions = [
            'tracking' => ['label' => 'Status Tracking', 'title' => 'Tracking Perjalanan', 'description' => 'Tahapan perjalanan barang dan kondisi pesanan.', 'applies_to' => 'Batch dan pesanan customer', 'application' => 'Pesanan mengikuti status batch, kecuali memiliki status khusus.'],
            'payment' => ['label' => 'Status Pembayaran', 'title' => 'Status Pembayaran', 'description' => 'Status pembayaran terpisah dari perjalanan barang.', 'applies_to' => 'Pembayaran pesanan', 'application' => 'Catat DP, pelunasan, dan refund melalui status pembayaran.'],
        ];
        $groupedStatuses = collect(['tracking' => $statuses->where('scope', '!=', 'payment'), 'payment' => $statuses->where('scope', 'payment')]);
        $requestedScope = request('scope');
        $activeScope = is_string($requestedScope) && array_key_exists($requestedScope, $scopeDefinitions) ? $requestedScope : 'tracking';
    @endphp

    <div class="status-manager-card status-folder-map" data-status-folder-map>
        <form class="order-table-toolbar status-manager-toolbar" data-auto-filter>
            <input type="hidden" name="scope" value="{{ $activeScope }}" data-status-folder-input>
            <input class="min-w-0 flex-1" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode status...">
            <select name="active">
                <option value="">Aktif & nonaktif</option>
                <option value="1" @selected(request('active') === '1')>Aktif saja</option>
                <option value="0" @selected(request('active') === '0')>Nonaktif saja</option>
            </select>
            <button class="order-table-toolbar-button" type="submit"><svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>Cari</button>
        </form>

        <div class="status-folder-tabs" role="tablist" aria-label="Kategori status">
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
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.75A1.75 1.75 0 0 1 4.75 5h4.1l1.8 2h8.6A1.75 1.75 0 0 1 21 8.75v8.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25V6.75Z"/></svg>
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
                        <span>{{ $scopeStatuses->count() }} status</span>
                    </div>

                    <div class="status-folder-application">
                        <span class="status-folder-application-icon" aria-hidden="true">
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
                            <thead><tr><th>No.</th><th>Status</th><th>Keterangan</th><th>Sifat</th><th>Dipakai</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse($scopeStatuses as $status)
                                    @php($usage = $status->batches_count + $status->member_orders_count + $status->order_items_count + $status->payment_member_orders_count + $status->old_histories_count + $status->new_histories_count)
                                    <tr class="{{ $status->is_active ? '' : 'bg-zinc-50 opacity-60' }}">
                                        <td data-label="No."><span class="grid h-7 w-7 place-items-center rounded-md bg-zinc-100">{{ $loop->iteration }}</span></td>
                                        <td data-label="Status"><x-status-badge :status="$status" /><div class="mt-1 text-zinc-400">{{ $status->code }}</div></td>
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
                                        <td data-label="Dipakai">{{ $usage }}×</td>
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

