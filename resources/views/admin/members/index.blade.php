<x-layouts.app title="Kelola Customer dan Group">
    <x-page-heading title="Kelola Customer dan Group" description="Kelola identitas, akun login, group, dan riwayat pesanan buyer.">
        <x-slot:action><a class="admin-primary-action" href="{{ route('admin.members.create', [], false) }}">+ Tambah pelanggan</a></x-slot:action>
    </x-page-heading>

    @include('admin.members.partials.navigation')
    <div class="mb-5 flex flex-wrap gap-3" aria-label="Jumlah anggota per group">
        @forelse($groups as $group)
            <a class="admin-form-secondary" href="{{ route('admin.customer-groups.show', $group) }}">{{ $group->name }} · {{ $group->members_count }} anggota</a>
        @empty<p>Belum ada label group. Buat label melalui tab Label Group.</p>@endforelse
    </div>
    <div class="order-table-card">
        <form class="order-table-toolbar">
            @if(request('registration') === 'pending')<input type="hidden" name="registration" value="pending">@endif
            <input class="min-w-0 flex-1" name="q" value="{{ request('q') }}" placeholder="Cari nama, username LINE, telepon, atau kode...">
            <select name="customer_group_id"><option value="">Semua group</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string) request('customer_group_id') === (string) $group->id)>{{ $group->name }}</option>@endforeach</select>
            <button class="order-table-toolbar-button" type="submit">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                Cari
            </button>
        </form>
        <div class="order-table-scroll">
            <table class="order-table responsive-card-table">
                <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Group</th><th>Akun login</th><th>Pesanan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td data-label="Pelanggan"><div class="order-table-primary">{{ $member->display_name }}</div><div class="order-table-secondary">#{{ $member->member_code }}</div></td>
                            <td data-label="Kontak"><div class="text-zinc-700">{{ $member->username ?: '-' }}</div><div class="order-table-secondary">{{ $member->phone ?: '-' }}</div></td>
                            <td data-label="Group">@if($member->customerGroup)<a class="order-table-action" href="{{ route('admin.customer-groups.show', $member->customerGroup) }}">{{ $member->customerGroup->name }}</a>@else-@endif</td>
                            <td data-label="Akun login">{{ $member->user?->registration_pending ? 'Menunggu persetujuan group' : ($member->is_active && $member->user?->password_set_at ? 'Siap login' : 'Belum siap') }}<div class="order-table-secondary">{{ $member->email ?: '-' }}</div></td>
                            <td data-label="Pesanan"><span class="font-semibold text-zinc-700">{{ $member->orders_count }}</span></td>
                            <td data-label="Status"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}"><span class="h-1.5 w-1.5 rounded-full {{ $member->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $member->user?->registration_pending ? 'Menunggu persetujuan' : ($member->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
                            <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.members.show', $member, false) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-12 text-center text-zinc-400">Belum ada pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer"><span>Menampilkan {{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} dari {{ $members->total() }} pelanggan</span><div>{{ $members->links() }}</div></div>
    </div>
</x-layouts.app>

