<x-layouts.app title="Detail {{ $member->display_name }}">
    <x-page-heading title="Detail Pelanggan" description="Kontak dan seluruh riwayat pembelian pelanggan dalam satu tampilan.">
        <x-slot:action><a class="admin-form-secondary" href="{{ route('admin.members.edit', $member, false) }}">Edit Pelanggan</a></x-slot:action>
    </x-page-heading>

    @if($member->user?->registration_pending)
        <section class="order-table-card mb-6 p-5">
            <h2 class="font-bold">Permintaan registrasi menunggu persetujuan</h2>
            <p class="mt-2 text-sm">Tentukan group sebelum menerima akun. Customer belum dapat login.</p>
            <form method="POST" action="{{ route('admin.members.approve', $member) }}" class="mt-4 flex flex-wrap gap-3">
                @csrf
                <label>Group <select name="customer_group_id" required class="rounded-lg border border-zinc-300 px-3 py-2"><option value="">Pilih group</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected((string) old('customer_group_id') === (string) $group->id)>{{ $group->name }}</option>@endforeach</select></label>
                <button class="admin-primary-action" type="submit" @disabled($groups->isEmpty())>Tentukan group & setujui akun</button>
            </form>
            @if($groups->isEmpty())<p class="mt-3 text-sm">Buat label group aktif terlebih dahulu di <a class="underline" href="{{ route('admin.customer-groups.create') }}">Label Group</a>.</p>@endif
        </section>
    @endif
    <article class="detail-record-card">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker">Data Pelanggan</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="detail-record-title">{{ $member->display_name }}</h2>
                    <span class="detail-record-state {{ $member->is_active ? 'detail-record-state-active' : 'detail-record-state-muted' }}">{{ $member->user?->registration_pending ? 'Menunggu persetujuan' : ($member->is_active ? 'Aktif' : 'Nonaktif') }}</span>
                </div>
                <p class="detail-record-description">#{{ $member->member_code }}</p>
            </div>
            <div class="detail-record-id">{{ $member->orders->count() }} pesanan</div>
        </header>

        <section class="detail-record-section">
            <div class="detail-record-section-heading">
                <div><h3>Biodata Pelanggan</h3><p>Identitas, kontak, dan informasi pendukung pelanggan.</p></div>
            </div>
            <div class="member-biodata-grid">
                <div class="member-biodata-item"><span>Username LINE</span><p>{{ $member->username ?: 'Belum diisi' }}</p></div>
                <div class="member-biodata-item"><span>Email</span><p>{{ $member->email ?: 'Belum diisi' }}</p></div>
                <div class="member-biodata-item"><span>Group</span><p>@if($member->customerGroup)<a href="{{ route('admin.customer-groups.show', $member->customerGroup) }}" class="underline">{{ $member->customerGroup->name }}</a>@else Belum ada group @endif</p></div>
                <div class="member-biodata-item"><span>Akun login</span><p>{{ $member->user?->registration_pending ? 'Menunggu persetujuan admin' : ($member->user?->password_set_at ? 'Siap login dengan username LINE dan password' : 'Belum siap — isi email dan password melalui Edit Pelanggan') }}</p></div>
                <div class="member-biodata-item"><span>Nomor telepon</span><p>{{ $member->phone ?: 'Belum diisi' }}</p></div>
                <div class="member-biodata-item"><span>Terdaftar sejak</span><p>{{ $member->created_at->format('d M Y') }}</p></div>
                <div class="member-biodata-item"><span>Alamat</span><p class="whitespace-pre-line">{{ $member->address ?: 'Belum ada alamat.' }}</p></div>
                <div class="member-biodata-item member-biodata-wide"><span>Catatan</span><p>{{ $member->notes ?: 'Belum ada catatan.' }}</p></div>
            </div>
        </section>

        <section class="detail-record-section detail-record-table-section">
            <div class="detail-record-section-heading detail-record-table-heading">
                <div><h3>Daftar Pembelian</h3><p>Pesanan pelanggan diurutkan berdasarkan pembaruan terakhir.</p></div>
            </div>
            <div class="order-table-scroll">
                <table class="order-table responsive-card-table">
                    <thead><tr><th>Pesanan</th><th>Batch</th><th>Tracking</th><th>Pembayaran</th><th>Jumlah item</th><th>Update terakhir</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        @forelse ($member->orders as $order)
                            <tr>
                                <td data-label="Pesanan"><div class="order-table-primary text-violet-700">{{ $order->order_code }}</div></td>
                                <td data-label="Batch"><div class="order-table-primary">{{ $order->batch->batch_number }}</div><div class="order-table-secondary">{{ $order->batch->batch_name ?: 'Tanpa nama batch' }}</div></td>
                                <td data-label="Status"><x-status-badge :status="$order->effective_status" /></td>
                                <td data-label="Pembayaran"><x-status-badge :status="$order->paymentStatus" /></td>
                                <td data-label="Jumlah item" class="font-semibold text-zinc-700">{{ $order->items->sum('quantity') }} item</td>
                                <td data-label="Update terakhir"><div class="text-zinc-700">{{ $order->updated_at->format('d M Y') }}</div><div class="order-table-secondary">{{ $order->updated_at->format('H:i') }}</div></td>
                                <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.member-orders.show', $order, false) }}">Detail</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-zinc-500">Belum ada pembelian untuk pelanggan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </article>
</x-layouts.app>
