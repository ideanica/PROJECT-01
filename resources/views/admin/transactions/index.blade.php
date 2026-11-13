<x-layouts.app title="Transaksi">
    @php
        $formatMoney = fn ($amount) => 'Rp '.number_format((float) $amount, fmod((float) $amount, 1) > 0 ? 2 : 0, ',', '.');
        $statusLabels = ['pending' => 'Menunggu verifikasi', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak'];
        $activeStatus = request('status');
        $hasFilters = request()->hasAny(['q', 'status', 'type', 'date_from', 'date_to']);
    @endphp

    <div class="[&_h1]:text-black!">
        <x-page-heading title="Transaksi" description="Periksa bukti pembayaran customer. Transaksi menunggu verifikasi ditampilkan paling atas." />
    </div>

    <details class="order-table-card mb-6 before:bg-none!" @if($errors->has('dp_percentage')) open @endif>
        <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2 px-4 py-3.5 text-[12px] font-semibold text-[#275a3a] sm:px-5">
            <span class="inline-flex items-center gap-2">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10" cy="10" r="7.5"/><path d="M10 6.5v4l2.5 1.5"/></svg>
                Pengaturan DP · {{ number_format($dpPercentage, 2, ',', '.') }}%
            </span>
            <span class="text-[11px] font-medium text-zinc-500">Buka untuk mengubah</span>
        </summary>
        <div class="border-t border-zinc-200 px-4 py-4 sm:px-5">
            <p class="text-xs leading-relaxed text-zinc-500">Persentase dari total harga barang untuk pilihan DP. Berlaku sebelum bukti pertama dikirim; pesanan yang sudah mengirim bukti mempertahankan persentasenya. Pilihan Lunas dan EMS tidak dipengaruhi.</p>
            <form method="POST" action="{{ route('admin.transactions.settings') }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf @method('PUT')
                <label class="block">
                    <span class="mb-1.5 block text-[11px] font-bold text-zinc-600">Persentase DP (%)</span>
                    <input type="number" name="dp_percentage" min="1" max="99.99" step="0.01" value="{{ old('dp_percentage', $dpPercentage) }}" required class="h-9 w-36 rounded-xl border border-zinc-200 bg-white px-3 text-sm text-zinc-700 outline-none transition focus:border-[#2f6b46] focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]">
                </label>
                <button class="admin-form-primary" type="submit">Simpan persentase DP</button>
            </form>
            @error('dp_percentage')<p class="mt-2 text-[11px] font-semibold text-red-600">{{ $message }}</p>@enderror
        </div>
    </details>

    <p class="mb-3 text-xs font-semibold text-zinc-600">Ringkasan seluruh transaksi</p>
    <section class="mb-6 grid gap-3 sm:grid-cols-3" aria-label="Ringkasan seluruh transaksi">
        @foreach($statusLabels as $status => $label)
            @php($summaryRow = $summary->get($status))
            <a
                href="{{ route('admin.transactions.index', ['status' => $status]) }}"
                @if($activeStatus === $status) aria-current="true" @endif
                class="rounded-2xl border p-4 shadow-sm transition {{ $activeStatus === $status ? 'border-[#2f6b46] bg-[#eef4ef]' : 'border-zinc-200 bg-white hover:border-[#c3d9ca] hover:bg-[#f6faf6]' }}"
            >
                <span class="text-xs font-semibold {{ $activeStatus === $status ? 'text-[#275a3a]' : 'text-zinc-500' }}">{{ $label }}</span>
                <strong class="mt-1 block text-2xl text-zinc-900">{{ $summaryRow?->total ?? 0 }}</strong>
                <small class="text-xs text-zinc-500">{{ $formatMoney($summaryRow?->amount ?? 0) }}</small>
            </a>
        @endforeach
    </section>

    <div class="order-table-card before:bg-none!">
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="border-b border-zinc-200 px-4 py-3 text-[12px] sm:px-5">
            <div class="grid grid-cols-1 items-center gap-1.5 min-[480px]:grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-wrap [&_.select2-container]:w-full! lg:[&_.select2-container]:w-auto!">
                <label class="flex h-9 w-full items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition focus-within:border-[#2f6b46]/45 focus-within:shadow-[0_0_0_4px_rgba(47,107,70,0.12)] min-[480px]:col-span-2 sm:col-span-3 lg:w-[18rem]">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-zinc-700" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    <input class="h-full w-full border-0! bg-transparent! p-0! text-zinc-700 shadow-none! outline-none! focus:shadow-none!" name="q" maxlength="100" value="{{ request('q') }}" aria-label="Cari customer, kode pesanan, atau batch" placeholder="Cari customer, kode pesanan, atau batch...">
                </label>

                <select name="status" aria-label="Filter status transaksi" class="h-8 w-full cursor-pointer rounded-xl border-zinc-200! bg-white! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]! lg:w-auto">
                    <option value="">Semua status</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($activeStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="type" aria-label="Filter jenis pembayaran" class="h-8 w-full cursor-pointer rounded-xl border-zinc-200! bg-white! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]! lg:w-auto">
                    <option value="">Semua jenis</option>
                    @foreach(\App\Models\OrderPayment::CUSTOMER_PAYMENT_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <div class="flex h-9 w-full items-center gap-1.5 rounded-md border border-zinc-200 bg-white px-2 shadow-[0_1px_2px_rgba(24,24,27,0.05)] min-[480px]:col-span-2 sm:col-span-3 lg:w-auto">
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_from" type="date" value="{{ request('date_from') }}" aria-label="Tanggal transaksi mulai">
                    <span class="text-zinc-300">—</span>
                    <input class="h-full min-w-0 flex-1 border-0! bg-transparent! p-0! text-zinc-600! shadow-none! outline-none! focus:shadow-none! lg:w-[6.75rem] lg:flex-none" name="date_to" type="date" value="{{ request('date_to') }}" aria-label="Tanggal transaksi akhir">
                </div>

                <button class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-[14px] border border-zinc-200 bg-white px-4 font-semibold text-[#27272a] shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition hover:bg-[#f4f4f5] focus:outline-none focus:ring-4 focus:ring-zinc-900/5 min-[480px]:col-span-2 sm:col-span-1 lg:w-auto" type="submit">
                    <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
                    Cari
                </button>
                @if($hasFilters)<a class="admin-form-secondary" href="{{ route('admin.transactions.index') }}">Reset filter</a>@endif
            </div>
        </form>

        <div class="order-table-scroll">
            <table class="order-table responsive-card-table [&_tbody_tr:hover]:bg-[#fafafa]!">
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
                        <tr><td colspan="5" class="py-12 text-center text-zinc-400">Tidak ada transaksi yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer">
            <span>Menampilkan {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} dari {{ $payments->total() }} transaksi</span>
            <div>{{ $payments->links('admin.partials.pagination') }}</div>
        </div>
    </div>
</x-layouts.app>
