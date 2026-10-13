<div class="order-table-scroll">
    <table class="order-table responsive-card-table">
        <thead><tr><th>Label Group</th><th>Keterangan</th><th>Jumlah anggota</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($groups as $group)
                <tr>
                    <td data-label="Label Group"><span class="font-semibold" style="color: {{ $group->color }}">{{ $group->name }}</span></td>
                    <td data-label="Keterangan">{{ $group->description ?: '-' }}</td>
                    <td data-label="Jumlah anggota">{{ $group->members_count }}</td>
                    <td data-label="Status">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td data-card-action><a class="order-table-action" href="{{ route('admin.customer-groups.show', $group) }}">Detail customer</a> <a class="order-table-action" href="{{ route('admin.customer-groups.edit', $group) }}">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-zinc-500">Belum ada group yang sesuai.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
