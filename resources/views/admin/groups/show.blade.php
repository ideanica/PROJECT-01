<x-layouts.app :title="'Group '.$group->name">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Detail Group" description="Informasi label group dan daftar customer yang tergabung di dalamnya.">
            <x-slot:action>
                <div class="flex flex-wrap gap-2">
                    <a class="admin-form-secondary" href="{{ route('admin.customer-groups.edit', $group) }}">Edit Group</a>
                    @if($group->is_active)
                        <form method="POST" action="{{ route('admin.customer-groups.destroy', $group) }}" onsubmit="return confirm('Nonaktifkan group ini? Customer yang sudah tergabung tetap tersimpan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-form-danger-action">Nonaktifkan Group</button>
                        </form>
                    @endif
                </div>
            </x-slot:action>
        </x-page-heading>
    </div>

    <article class="detail-record-card before:bg-none! [&_.detail-record-hero]:bg-[linear-gradient(135deg,#ffffff,#f6faf6_58%,#eef6ef)]!">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker text-[#275a3a]!">Label Group</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="detail-record-title" style="color: {{ $group->color }}">{{ $group->name }}</h2>
                    <span class="detail-record-state {{ $group->is_active ? 'detail-record-state-active' : 'detail-record-state-muted' }}">{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <p class="detail-record-description">{{ $group->description ?: 'Belum ada keterangan untuk group ini.' }}</p>
            </div>
            <div class="detail-record-id">ID #{{ str_pad($group->id, 4, '0', STR_PAD_LEFT) }}</div>
        </header>

        <section class="detail-record-section">
            <div class="detail-record-summary sm:grid-cols-2 xl:grid-cols-4">
                <div class="detail-record-field detail-record-field-violet">
                    <span>Jumlah customer</span>
                    <strong>{{ $members->total() }} customer</strong>
                    <small>Customer yang memakai label ini</small>
                </div>
                <div class="detail-record-field detail-record-field-blue">
                    <span>Status group</span>
                    <strong>{{ $group->is_active ? 'Aktif' : 'Nonaktif' }}</strong>
                    <small>{{ $group->is_active ? 'Tersedia pada pilihan registrasi' : 'Hanya tersimpan pada customer lama' }}</small>
                </div>
                <div class="detail-record-field detail-record-field-cyan">
                    <span>Warna label</span>
                    <strong class="flex items-center gap-2"><span class="h-3 w-3 rounded-full" style="background: {{ $group->color }}"></span>{{ $group->color }}</strong>
                    <small>Penanda warna pada daftar customer</small>
                </div>
                <div class="detail-record-field detail-record-field-amber">
                    <span>Dibuat</span>
                    <strong>{{ $group->created_at->format('d M Y') }}</strong>
                    <small>Diperbarui {{ $group->updated_at->format('d M Y, H:i') }}</small>
                </div>
            </div>
        </section>

        <section class="detail-record-section detail-record-table-section">
            <div class="detail-record-section-heading detail-record-table-heading">
                <div>
                    <h3>Daftar Customer</h3>
                    <p>Customer yang tergabung ke label {{ $group->name }}, diurutkan berdasarkan nama.</p>
                </div>
            </div>
            <div class="order-table-scroll">
                <table class="order-table responsive-card-table customer-card-table">
                    <thead><tr><th>Customer</th><th>Username</th><th>Email</th><th>Pesanan</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td data-label="Customer"><div class="order-table-primary">{{ $member->display_name }}</div><div class="order-table-secondary">{{ $member->member_code }}</div></td>
                                <td data-label="Username">{{ $member->username ?: '-' }}</td>
                                <td data-label="Email">{{ $member->email ?: '-' }}</td>
                                <td data-label="Pesanan">{{ $member->orders_count }}</td>
                                <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.members.show', $member) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">Belum ada customer dalam group ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="order-table-footer detail-record-table-footer">
                <span>Menampilkan {{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} dari {{ $members->total() }} customer</span>
                <div>{{ $members->links('admin.partials.pagination') }}</div>
            </div>
        </section>
    </article>
</x-layouts.app>
