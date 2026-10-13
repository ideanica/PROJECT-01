<x-layouts.app :title="'Group '.$group->name">
    <x-page-heading :title="'Group '.$group->name" :description="$group->description ?: 'Daftar customer dalam group ini.'">
        <x-slot:action><a class="admin-form-secondary" href="{{ route('admin.customer-groups.edit', $group) }}">Edit Group</a></x-slot:action>
    </x-page-heading>
    <div class="mb-5 flex flex-wrap items-center gap-4">
        <span class="font-semibold">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }} · {{ $members->total() }} customer</span>
        @if($group->is_active)
            <form method="POST" action="{{ route('admin.customer-groups.destroy', $group) }}">@csrf @method('DELETE')<button type="submit" class="admin-form-secondary">Nonaktifkan group</button></form>
        @endif
    </div>
    <div class="order-table-card"><div class="order-table-scroll">
        <table class="order-table responsive-card-table">
            <thead><tr><th>Customer</th><th>Username</th><th>Email</th><th>Pesanan</th><th>Aksi</th></tr></thead>
            <tbody>@forelse($members as $member)
                <tr><td data-label="Customer">{{ $member->display_name }}</td><td data-label="Username">{{ $member->username }}</td><td data-label="Email">{{ $member->email ?: '-' }}</td><td data-label="Pesanan">{{ $member->orders_count }}</td><td data-card-action><a class="order-table-action" href="{{ route('admin.members.show', $member) }}">Detail buyer</a></td></tr>
            @empty<tr><td colspan="5" class="py-10 text-center text-zinc-500">Belum ada customer dalam group ini.</td></tr>@endforelse</tbody>
        </table>
    </div><div class="order-table-footer">{{ $members->links() }}</div></div>
</x-layouts.app>
