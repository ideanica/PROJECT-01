<x-layouts.app title="Pelanggan">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Pelanggan" description="Kelola data kontak pembeli yang tersedia pada form pesanan.">
            <x-slot:action><a class="admin-primary-action" href="{{ route('admin.members.create', [], false) }}">+ Tambah pelanggan</a></x-slot:action>
        </x-page-heading>
    </div>

    <div class="order-table-card">
        <form class="order-table-toolbar">
            <input class="min-w-0 flex-1" name="q" value="{{ request('q') }}" placeholder="Cari nama, username LINE, telepon, atau kode...">
            <button class="order-table-toolbar-button" type="submit">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                Cari
            </button>
        </form>
        <div class="order-table-scroll">
            <table class="order-table responsive-card-table">
                <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Pesanan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td data-label="Pelanggan"><div class="order-table-primary">{{ $member->display_name }}</div><div class="order-table-secondary">#{{ $member->member_code }}</div></td>
                            <td data-label="Kontak"><div class="text-zinc-700">{{ $member->username ?: '-' }}</div><div class="order-table-secondary">{{ $member->phone ?: '-' }}</div></td>
                            <td data-label="Pesanan"><span class="font-semibold text-zinc-700">{{ $member->orders_count }}</span></td>
                            <td data-label="Status"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}"><span class="h-1.5 w-1.5 rounded-full {{ $member->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.members.show', $member, false) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-zinc-400">Belum ada pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer"><span>Menampilkan {{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} dari {{ $members->total() }} pelanggan</span><div>{{ $members->links() }}</div></div>
    </div>
</x-layouts.app>
