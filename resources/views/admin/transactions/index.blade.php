<x-layouts.app title="Transaksi">
    @php($formatMoney = fn ($amount) => 'Rp '.number_format((float) $amount, fmod((float) $amount, 1) > 0 ? 2 : 0, ',', '.'))
    <x-page-heading title="Transaksi" description="Periksa bukti pembayaran customer. Transaksi menunggu verifikasi ditampilkan paling atas." />
    <details class="transaction-panel mb-6" @if($errors->has('dp_percentage')) open @endif>
        <summary class="cursor-pointer font-semibold">Pengaturan DP · {{ number_format($dpPercentage, 2, ',', '.') }}%</summary>
        <p class="mt-3 text-sm text-zinc-500">Persentase dari total harga barang untuk pilihan DP. Berlaku sebelum bukti pertama dikirim; pesanan yang sudah mengirim bukti mempertahankan persentasenya. Pilihan Lunas dan EMS tidak dipengaruhi.</p>
        <form method="POST" action="{{ route('admin.transactions.settings') }}" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf @method('PUT')
            <label class="block text-sm font-semibold"><span class="block mb-2">Persentase DP (%)</span><input type="number" name="dp_percentage" min="1" max="99.99" step="0.01" value="{{ old('dp_percentage', $dpPercentage) }}" required></label>
            <button class="admin-form-primary" type="submit">Simpan persentase DP</button>
        </form>
    </details>
    <p class="mb-3 text-xs text-zinc-500">Ringkasan seluruh transaksi</p>
    <section class="grid gap-3 mb-6 sm:grid-cols-3" aria-label="Ringkasan seluruh transaksi">
        @foreach(['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'] as $status => $label)
            <a href="{{ route('admin.transactions.index', ['status' => $status]) }}" class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm"><span class="text-sm text-zinc-500">{{ $label }}</span><strong class="block mt-2 text-2xl">{{ $summary->get($status)?->total ?? 0 }}</strong><small class="text-zinc-500">{{ $formatMoney($summary->get($status)?->amount ?? 0) }}</small></a>
        @endforeach
    </section>
    <div class="order-table-card">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="transaction-filters">
            <label class="transaction-search">Cari transaksi<input type="search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Customer, kode pesanan, atau batch"></label>
            <label>Status<select name="status"><option value="">Semua status</option>@foreach(['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Jenis pembayaran<select name="type"><option value="">Semua jenis</option>@foreach(\App\Models\OrderPayment::CUSTOMER_PAYMENT_LABELS as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Dikirim sejak<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
            <label>Sampai<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            <div class="flex items-center gap-2"><button class="admin-form-primary" type="submit">Terapkan</button><a class="admin-form-secondary" href="{{ route('admin.transactions.index') }}">Reset</a></div>
        </form>
        <div class="order-table-scroll">
            <table class="order-table responsive-card-table">
                <thead><tr><th>Customer / Pesanan</th><th>Pembayaran</th><th>Dikirim</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td data-label="Customer"><div class="order-table-primary">{{ $payment->order->member->display_name }}</div><div class="order-table-secondary">{{ $payment->order->order_code }} · {{ $payment->order->batch->batch_number }}</div></td>
                            <td data-label="Pembayaran"><div class="order-table-primary">{{ $formatMoney($payment->amount) }}</div><div class="order-table-secondary">{{ $payment->type_label }}</div></td>
                            <td data-label="Dikirim"><div>{{ $payment->created_at?->format('d M Y, H:i') ?? 'Tanggal tidak tersedia' }}</div><div class="order-table-secondary">{{ $payment->customer_submitted ? 'Dikirim customer' : 'Dicatat admin / data awal' }}</div></td>
                            <td data-label="Status"><x-status-badge :label="$payment->status_label" :color="match($payment->status) { 'verified' => '#bbf7d0', 'rejected' => '#fecaca', default => '#fef08a' }" /></td>
                            <td data-card-action><a class="order-table-action" href="{{ route('admin.transactions.show', $payment) }}">{{ $payment->status === 'pending' ? 'Periksa bukti' : 'Detail' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-zinc-500">Tidak ada transaksi yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer"><span>{{ $payments->total() }} transaksi</span>{{ $payments->links() }}</div>
    </div>
</x-layouts.app>
