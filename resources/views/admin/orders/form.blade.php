@php
    $selectedBatchId = (int) old('batch_id', $order->batch_id ?: request('batch_id'));
    $itemsAreLocked = $order->exists && ($order->batch?->orders_locked || $order->is_refunded);
    $rows = old('items', $order->items?->toArray() ?: [['item_name' => '', 'quantity' => 1]]);
    $selectedMemberId = old('member_id', $order->member_id);
@endphp

<x-layouts.app title="{{ $order->exists ? 'Edit Pesanan' : 'Tambah Pesanan' }}">
    <x-admin-form-shell
        title="{{ $order->exists ? 'Edit Pesanan' : 'Tambah Pesanan' }}"
        eyebrow="Pesanan dalam Batch"
        description="Pilih customer yang memiliki akun login dan aktif. Customer baru dapat mendaftar menggunakan username dan password."
        max-width="max-w-6xl"
    >
        <form method="POST" action="{{ $order->exists ? route('admin.member-orders.update', $order, false) : route('admin.member-orders.store', [], false) }}">
            @csrf
            @if($order->exists) @method('PUT') @endif

            <div class="admin-form-body">
                <x-admin-form-intro
                    title="Satu Form untuk Satu Pesanan"
                    description="Kode pesanan dibuat otomatis. Member dipilih dari daftar yang memiliki akun login aktif (eligible untuk order baru)."
                />

                <x-admin-form-section title="Identitas Pesanan">
                    <div class="space-y-4">
                        <div class="admin-form-readonly admin-form-code-preview">
                            <span>{{ $order->exists ? 'Kode pesanan / tracking' : 'Kode pesanan otomatis' }}</span>
                            <strong>{{ $order->exists ? $order->order_code : 'ORD-'.now()->format('ym').'-XXXXXX' }}</strong>
                            <small class="admin-form-help">Kode ini menjadi kunci tracking status dan tagihan Pajak/EMS.</small>
                        </div>
                        <div>
                            <label class="block">
                                <span>Pilih Member <span class="text-red-500" aria-hidden="true">*</span></span>
                                <select name="member_id" required class="w-full rounded border border-zinc-300 bg-white px-3 py-2 text-zinc-900 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20">
                                    <option value="">Pilih member...</option>
                                    @foreach($eligibleMembers as $member)
                                        <option value="{{ $member->id }}" @selected($selectedMemberId == $member->id)>
                                            {{ $member->display_name }} ({{ $member->member_code }}) — {{ $member->username }} — {{ $member->eligibility_status_label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="admin-form-help">
                                    Hanya member yang sudah <strong>memiliki akun login</strong> dan <strong>aktif</strong> yang dapat dipilih untuk order baru.
                                    Buyer lama yang belum memiliki akun login dapat disiapkan akunnya melalui Kelola Customer.
                                </small>
                            </label>
                        </div>
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Batch dan Pembayaran">
                    <div class="grid items-start gap-4 md:grid-cols-2">
                        @if($itemsAreLocked)
                            <div class="admin-form-readonly">
                                <span>Batch pembelian</span>
                                <strong>{{ $order->batch->batch_number }}</strong>
                                <small class="admin-form-help">Batch terkunci karena pesanan sudah diproses.</small>
                                <input type="hidden" name="batch_id" value="{{ $order->batch_id }}">
                            </div>
                        @else
                            <label class="block">
                                <span>Batch pembelian</span>
                                <select name="batch_id" required>
                                    <option value="">Pilih batch</option>
                                    @foreach($batches as $batch)
                                        <option value="{{ $batch->id }}" @selected($selectedBatchId === $batch->id)>{{ $batch->batch_number }}{{ $batch->batch_name ? ' · '.$batch->batch_name : '' }}</option>
                                    @endforeach
                                </select>
                                <span class="admin-form-help">Pesanan mengikuti progress batch ini.</span>
                            </label>
                        @endif
                        <label class="block">
                            <span>Status pembayaran pesanan</span>
                            <select name="payment_status_id">
                                <option value="">Belum ditentukan</option>
                                @foreach($paymentStatuses as $status)
                                    <option value="{{ $status->id }}" @selected(old('payment_status_id', $order->payment_status_id) == $status->id)>{{ $status->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label class="mt-4 block"><span>Catatan pesanan</span><textarea name="notes" rows="3" placeholder="Informasi tambahan untuk pesanan ini">{{ old('notes', $order->notes) }}</textarea></label>
                </x-admin-form-section>

                <x-admin-form-section title="Tagihan Pajak & EMS">
                    <div class="grid items-start gap-4 md:grid-cols-3">
                        <x-text-input label="Nominal tagihan" name="ems_tax_amount" type="number" min="0" step="0.01" :value="old('ems_tax_amount', $order->ems_tax_amount)" placeholder="Contoh: 85000" />
                        <label class="block">
                            <span>Status tagihan</span>
                            <select name="ems_tax_status">
                                <option value="not_billed" @selected(old('ems_tax_status', $order->ems_tax_status ?: 'not_billed') === 'not_billed')>Belum ditagihkan</option>
                                <option value="unpaid" @selected(old('ems_tax_status', $order->ems_tax_status) === 'unpaid')>Menunggu pembayaran</option>
                                <option value="paid" @selected(old('ems_tax_status', $order->ems_tax_status) === 'paid')>Lunas</option>
                            </select>
                        </label>
                        <x-text-input label="Jatuh tempo" name="ems_tax_due_date" type="date" :value="old('ems_tax_due_date', $order->ems_tax_due_date?->format('Y-m-d'))" />
                    </div>
                    <label class="mt-4 block">
                        <span>Rincian tagihan</span>
                        <textarea name="ems_tax_notes" rows="3" maxlength="1000" placeholder="Contoh: Pajak impor dan ongkir EMS Korea–Indonesia">{{ old('ems_tax_notes', $order->ems_tax_notes) }}</textarea>
                        <span class="admin-form-help">Saat nominal pertama kali disimpan, status otomatis menjadi “Menunggu pembayaran” dan tagihan tampil pada kode tracking pesanan ini.</span>
                    </label>
                </x-admin-form-section>

                <x-admin-form-section title="Item Pesanan">
                    @if($itemsAreLocked)
                        <div class="admin-form-lock-notice"><strong>Komposisi pesanan sudah dikunci</strong><p>Item, jumlah, harga, dan batch tidak dapat diubah setelah pesanan diproses.</p></div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach($order->items as $item)
                                <div class="admin-form-readonly">
                                    <span>{{ $item->item_name }}{{ $item->variant ? ' · '.$item->variant : '' }}</span>
                                    <strong>{{ $item->quantity }} × {{ $item->unit_price ? 'Rp '.number_format($item->unit_price, 0, ',', '.') : '-' }}</strong>
                                    <small class="admin-form-help">Subtotal: {{ $item->subtotal ? 'Rp '.number_format($item->subtotal, 0, ',', '.') : '-' }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-zinc-500">Tulis nama item langsung; tidak perlu membuat master produk.</p>
                            <button type="button" data-add-item class="admin-form-inline-action">+ Tambah Item</button>
                        </div>
                        <div data-items class="space-y-3">
                            @foreach($rows as $i => $item)
                                @include('admin.orders.partials.item-row', ['index' => $i, 'item' => $item])
                            @endforeach
                        </div>
                        <template data-item-template>@include('admin.orders.partials.item-row', ['index' => '__INDEX__', 'item' => ['item_name' => '', 'quantity' => 1]])</template>
                    @endif
                </x-admin-form-section>
            </div>

            <footer class="admin-form-footer">
                <p class="admin-form-footer-note">Pelanggan, item, dan tagihan tersambung ke kode pesanan yang sama.</p>
                <div class="admin-form-actions">
                    <a class="admin-form-secondary" href="{{ $order->exists ? route('admin.member-orders.show', $order, false) : route('admin.member-orders.index', [], false) }}">Batal</a>
                    <button type="submit" class="admin-form-primary">Simpan Pesanan</button>
                </div>
            </footer>
        </form>
    </x-admin-form-shell>

    @unless($itemsAreLocked)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const container = document.querySelector('[data-items]');
                const template = document.querySelector('[data-item-template]');
                const addButton = document.querySelector('[data-add-item]');
                let nextIndex = {{ count($rows) }};
                addButton?.addEventListener('click', () => container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++)));
                container?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-item]');
                    if (!button) return;
                    const rows = container.querySelectorAll('[data-item-row]');
                    if (rows.length === 1) {
                        button.closest('[data-item-row]').querySelectorAll('input').forEach((input) => input.value = input.matches('[data-item-qty]') ? 1 : '');
                        return;
                    }
                    button.closest('[data-item-row]').remove();
                });
            });
        </script>
    @endunless
</x-layouts.app>

