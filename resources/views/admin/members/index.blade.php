<x-layouts.app title="Kelola Customer dan Group">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Kelola Customer dan Group" description="Kelola identitas, akun login, group, dan riwayat pesanan buyer.">
            <x-slot:action><a class="admin-form-primary" href="{{ route('admin.members.create', [], false) }}">+ Tambah pelanggan</a></x-slot:action>
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
                        <table class="order-table responsive-card-table customer-card-table">
                            <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Group</th><th>Akun login</th><th>Pesanan</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse ($members as $member)
                                    <tr>
                                        <td data-label="Pelanggan"><div class="order-table-primary">{{ $member->display_name }}</div><div class="order-table-secondary">#{{ $member->member_code }}</div></td>
                                        <td data-label="Kontak"><div class="text-zinc-700">{{ $member->username ?: '-' }}</div><div class="order-table-secondary">{{ $member->phone ?: '-' }}</div></td>
                                        <td data-label="Group">@if($member->customerGroup)<a class="order-table-action" href="{{ route('admin.customer-groups.show', $member->customerGroup) }}">{{ $member->customerGroup->name }}</a>@else-@endif</td>
                                        <td data-label="Akun login">{{ $member->user?->registration_pending ? 'Menunggu persetujuan group' : ($member->is_active && $member->user?->password_set_at ? 'Siap login' : 'Belum siap') }}<div class="order-table-secondary">{{ $member->email ?: '-' }}</div></td>
                                        <td data-label="Pesanan"><span class="font-semibold text-zinc-700">{{ $member->orders_count }}</span></td>
                                        <td data-label="Status"><span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $member->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $member->user?->registration_pending ? 'Menunggu persetujuan' : ($member->is_active ? 'Aktif' : 'Nonaktif') }}</span></td>
                                        <td data-card-action class="text-right">
                                            @if($member->user?->registration_pending)
                                                <div class="relative inline-block text-left" data-row-menu>
                                                    <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-[14px] border border-zinc-200 bg-white text-zinc-500 transition hover:border-[#c3d9ca] hover:bg-[#eef4ef] hover:text-[#275a3a] focus-visible:border-[#2f6b46] focus-visible:shadow-[0_0_0_3px_rgba(47,107,70,0.12)] focus-visible:outline-none" aria-label="Aksi untuk {{ $member->display_name }}" aria-haspopup="menu" aria-expanded="false" data-row-menu-button>
                                                        <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true"><circle cx="10" cy="4" r="1.5"/><circle cx="10" cy="10" r="1.5"/><circle cx="10" cy="16" r="1.5"/></svg>
                                                    </button>
                                                    <div class="fixed z-50 w-48 overflow-hidden rounded-lg border border-zinc-200 bg-white py-1 text-left shadow-xl shadow-zinc-900/10" role="menu" aria-label="Aksi untuk {{ $member->display_name }}" data-row-menu-panel hidden>
                                                        <a role="menuitem" href="{{ route('admin.members.show', $member, false) }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:bg-zinc-50 hover:text-zinc-900">
                                                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 10S5.5 4.5 10 4.5 17.5 10 17.5 10 14.5 15.5 10 15.5 2.5 10 2.5 10Z"/><circle cx="10" cy="10" r="2.2"/></svg>
                                                            Detail
                                                        </a>
                                                        <button type="button" role="menuitem" class="flex w-full items-center gap-2 px-3 py-2 text-xs font-semibold text-[#275a3a] transition hover:bg-[#eef4ef]" data-status-modal-open="approve-member-{{ $member->id }}">
                                                            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m4 10.5 4 4 8-9"/></svg>
                                                            Setujui permintaan
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <a class="order-table-action" href="{{ route('admin.members.show', $member, false) }}">Detail</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-12 text-center text-zinc-400">Tidak ada customer yang cocok dengan pencarian atau filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="order-table-footer"><span>Menampilkan {{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} dari {{ $members->total() }} pelanggan</span><div>{{ $members->links('admin.partials.pagination') }}</div></div>
            </section>
        </div>
    </div>

    @foreach ($members->getCollection()->filter(fn ($member) => $member->user?->registration_pending) as $member)
        <div class="status-modal" data-status-modal="approve-member-{{ $member->id }}" role="dialog" aria-modal="true" aria-labelledby="approve-member-{{ $member->id }}-title" aria-hidden="true" hidden>
            <button type="button" class="status-modal-backdrop" data-status-modal-close aria-label="Tutup dialog"></button>
            <div class="status-modal-surface" tabindex="-1">
                <header class="status-modal-header">
                    <div>
                        <p>Permintaan registrasi</p>
                        <h2 id="approve-member-{{ $member->id }}-title">Permintaan registrasi menunggu persetujuan</h2>
                    </div>
                    <button type="button" class="status-modal-close" data-status-modal-close aria-label="Tutup">
                        <svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                    </button>
                </header>

                <div class="status-modal-body">
                    <div class="rounded-xl border border-[#c3d9ca] bg-[#eef4ef] px-4 py-3">
                        <p class="text-sm font-bold text-[#275a3a]">{{ $member->display_name }}</p>
                        <p class="mt-1 text-xs text-[#3f6b52]">#{{ $member->member_code }} · {{ $member->email ?: ($member->username ?: '-') }}</p>
                    </div>

                    <form id="approve-form-{{ $member->id }}" method="POST" action="{{ route('admin.members.approve', $member, false) }}" class="grid gap-3">
                        @csrf
                        <label class="grid gap-1.5">
                            <span>Group</span>
                            <p class="text-xs text-zinc-500">Customer belum dapat login sebelum group ditentukan dan akun disetujui.</p>
                            <select name="customer_group_id" required class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm">
                                <option value="">Pilih group</option>
                                @foreach ($activeGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="flex justify-end">
                        </div>
                    </form>

                    <p class="text-xs text-zinc-500">
                        @if ($activeGroups->isEmpty())
                            Belum ada group aktif. Buat dulu di
                        @else
                            Ingin menambah atau mengubah pilihan group? Kelola di
                        @endif
                        <a class="font-semibold text-[#275a3a] underline" href="{{ route('admin.customer-groups.index', [], false) }}">Label Group</a>.
                    </p>
                </div>

                <footer class="status-detail-footer status-modal-footer">
                    <button type="button" class="admin-form-secondary" data-status-modal-close>Tutup</button>
                    <button class="admin-primary-action" type="submit" form="approve-form-{{ $member->id }}" @disabled($activeGroups->isEmpty())>Tentukan group &amp; setujui akun</button>
                </footer>
            </div>
        </div>
    @endforeach
</x-layouts.app>

