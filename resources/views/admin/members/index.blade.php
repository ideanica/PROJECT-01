<x-layouts.app title="Kelola Customer dan Group">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Kelola Customer dan Group" description="Kelola identitas, akun login, group, dan riwayat pesanan buyer.">
            <x-slot:action><a class="admin-primary-action" href="{{ route('admin.members.create', [], false) }}">+ Tambah pelanggan</a></x-slot:action>
        </x-page-heading>
    </div>

    <div class="order-table-card before:bg-none!">
        @include('admin.members.partials.filters')
        @include('admin.members.partials.navigation')
        <div class="status-folder-content">
            <section class="status-folder-panel">
                <div class="status-folder-heading">
                    <div>
                        <h2>{{ request('registration') === 'pending' ? 'Permintaan Registrasi Customer' : 'Daftar Customer / Buyer' }}</h2>
                        <p>{{ request('registration') === 'pending' ? 'Customer yang menunggu penentuan group dan persetujuan akun.' : 'Kelola identitas, group, dan kesiapan akun login customer.' }}</p>
                    </div>
                    <span class="bg-[#eef4ef]! text-[#275a3a]!">{{ $members->total() }} customer</span>
                </div>
                <div class="status-table-frame">
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
                                    <tr><td colspan="7" class="py-12 text-center text-zinc-400">Tidak ada customer yang cocok dengan pencarian atau filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="order-table-footer"><span>Menampilkan {{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} dari {{ $members->total() }} pelanggan</span><div>{{ $members->links() }}</div></div>
            </section>
        </div>
    </div>
</x-layouts.app>

