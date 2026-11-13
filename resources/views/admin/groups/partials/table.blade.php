<div class="order-table-scroll">
    <table class="order-table responsive-card-table customer-card-table">
        <thead><tr><th>Label Group</th><th>Keterangan</th><th>Jumlah anggota</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($groups as $group)
                <tr>
                    <td data-label="Label Group"><span class="font-semibold" style="color: {{ $group->color }}">{{ $group->name }}</span></td>
                    <td data-label="Keterangan">{{ $group->description ?: '-' }}</td>
                    <td data-label="Jumlah anggota">{{ $group->members_count }}</td>
                    <td data-label="Status">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td data-card-action class="text-center">
                        <div class="relative inline-block text-left" data-row-menu>
                            <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-[14px] border border-zinc-200 bg-white text-zinc-500 transition hover:border-[#c3d9ca] hover:bg-[#eef4ef] hover:text-[#275a3a] focus-visible:border-[#2f6b46] focus-visible:shadow-[0_0_0_3px_rgba(47,107,70,0.12)] focus-visible:outline-none" aria-label="Aksi untuk group {{ $group->name }}" aria-haspopup="menu" aria-expanded="false" data-row-menu-button>
                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
                            </button>
                            <div class="fixed z-50 w-48 overflow-hidden rounded-lg border border-zinc-200 bg-white py-1 text-left shadow-xl shadow-zinc-900/10" role="menu" aria-label="Aksi untuk group {{ $group->name }}" data-row-menu-panel hidden>
                                <a role="menuitem" href="{{ route('admin.customer-groups.show', $group) }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900">
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 10S5.5 4.5 10 4.5 17.5 10 17.5 10 14.5 15.5 10 15.5 2.5 10 2.5 10Z"/><circle cx="10" cy="10" r="2.2"/></svg>
                                    Detail customer
                                </a>
                                <a role="menuitem" href="{{ route('admin.customer-groups.edit', $group) }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900">
                                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m4 16 1-3.5L13.5 4a1.9 1.9 0 0 1 2.7 2.7L7.5 15 4 16Z"/><path d="m12.5 5 2.5 2.5"/></svg>
                                    Edit
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-zinc-500">Belum ada group yang sesuai.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
