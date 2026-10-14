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
        description="Pilih buyer aktif. Buyer tidak wajib mempunyai akun login terlebih dahulu; history tetap melekat ke Member dan dapat di-claim saat akun dibuat."
        max-width="max-w-6xl"
    >
        <form method="POST" action="{{ $order->exists ? route('admin.member-orders.update', $order, false) : route('admin.member-orders.store', [], false) }}">
            @csrf
            @if($order->exists) @method('PUT') @endif

            <div class="admin-form-body">
                <x-admin-form-intro title="Satu Form untuk Satu Pesanan" description="Kode pesanan dibuat otomatis. Buyer unlinked tetap boleh dicatat selama Member aktif." />

                <x-admin-form-section title="Identitas Pesanan">
                    <div class="space-y-4">
                        <div class="admin-form-readonly admin-form-code-preview">
                            <span>{{ $order->exists ? 'Kode pesanan / tracking' : 'Kode pesanan otomatis' }}</span>
                            <strong>{{ $order->exists ? $order->order_code : 'ORD-'.now()->format('ym').'-XXXXXX' }}</strong>
                            <small class="admin-form-help">Kode ini tetap menjadi kunci tracking existing.</small>
                        </div>
                        @if($itemsAreLocked)
                            <div class="admin-form-readonly"><span>Buyer</span><strong>{{ $order->member->display_name }}</strong><small>Customer tidak dapat diganti setelah pesanan diproses.</small></div>
                            <input type="hidden" name="member_id" value="{{ $order->member_id }}">
                        @else
                        <label class="block">
                            <span>Pilih Member <span class="text-red-500">*</span></span>
                            <select name="member_id" required>
                                <option value="">Pilih buyer...</option>
                                @foreach($eligibleMembers as $member)
                                    <option value="{{ $member->id }}" @selected($selectedMemberId == $member->id)>{{ $member->display_name }} ({{ $member->member_code }}) · {{ $member->username }} - {{ $member->eligibility_status_label }}</option>
                                @endforeach
                            </select>
                            <small class="admin-form-help">Akun login bukan syarat untuk pencatatan order. Jika buyer mendaftar kemudian, account claim akan menghubungkan akun ke Member lama setelah approval admin.</small>
                        </label>
                        @endif
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Batch dan Pembayaran">
                    <div class="grid items-start gap-4 md:grid-cols-3">
                        @if($itemsAreLocked)
                            <div class="admin-form-readonly"><span>Batch pembelian</span><strong>{{ $order->batch->batch_number }}</strong><small class="admin-form-help">Batch terkunci karena pesanan sudah diproses.</small><input type="hidden" name="batch_id" value="{{ $order->batch_id }}"></div>
                        @else
                            <label class="block"><span>Batch pembelian</span><select name="batch_id" required><option value="">Pilih batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected($selectedBatchId === $batch->id)>{{ $batch->batch_number }}{{ $batch->batch_name ? ' · '.$batch->batch_name : '' }}</option>@endforeach</select><span class="admin-form-help">Pesanan mengikuti progress batch ini.</span></label>
                        @endif
                        <x-text-input label="GO / Group" name="go_number" :value="old('go_number', $order->go_number)" placeholder="Contoh: 4 atau GO-2" />
                        <label class="block"><span>Status pembayaran</span><select name="payment_status_id"><option value="">Belum ditentukan</option>@foreach($paymentStatuses as $status)<option value="{{ $status->id }}" @selected(old('payment_status_id', $order->payment_status_id) == $status->id)>{{ $status->name }}</option>@endforeach</select></label>
                    </div>
                    <label class="mt-4 block"><span>Catatan pesanan</span><textarea name="notes" rows="3" placeholder="Informasi tambahan untuk pesanan ini">{{ old('notes', $order->notes) }}</textarea></label>
                    @if($order->exists)<div class="mt-4"><a class="admin-form-secondary" href="{{ route('admin.member-orders.payments.index', $order, false) }}">Kelola payment ledger</a></div>@endif
                </x-admin-form-section>

                <x-admin-form-section title="Tagihan Pajak & EMS">
                    <div class="grid items-start gap-4 md:grid-cols-3">
                        <x-text-input label="Nominal tagihan" name="ems_tax_amount" type="number" min="0" step="0.01" :value="old('ems_tax_amount', $order->ems_tax_amount)" placeholder="Contoh: 85000" />
                        <label class="block"><span>Status tagihan</span><select name="ems_tax_status"><option value="not_billed" @selected(old('ems_tax_status', $order->ems_tax_status ?: 'not_billed') === 'not_billed')>Belum ditagihkan</option><option value="unpaid" @selected(old('ems_tax_status', $order->ems_tax_status) === 'unpaid')>Menunggu pembayaran</option><option value="paid" @selected(old('ems_tax_status', $order->ems_tax_status) === 'paid')>Lunas</option></select></label>
                        <x-text-input label="Jatuh tempo" name="ems_tax_due_date" type="date" :value="old('ems_tax_due_date', $order->ems_tax_due_date?->format('Y-m-d'))" />
                    </div>
                    <label class="mt-4 block"><span>Rincian tagihan</span><textarea name="ems_tax_notes" rows="3" maxlength="1000">{{ old('ems_tax_notes', $order->ems_tax_notes) }}</textarea></label>
                </x-admin-form-section>

                <x-admin-form-section title="Item Pesanan">
                    @if($itemsAreLocked)
                        <div class="admin-form-lock-notice"><strong>Komposisi pesanan sudah dikunci</strong><p>Item, jumlah, harga, dan batch tidak dapat diubah setelah pesanan diproses.</p></div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($order->items as $item)<div class="admin-form-readonly"><span>{{ $item->item_name }}{{ $item->variant ? ' · '.$item->variant : '' }}</span><strong>{{ $item->quantity }} × {{ $item->unit_price ? 'Rp '.number_format($item->unit_price, 0, ',', '.') : '-' }}</strong><small class="admin-form-help">Subtotal: {{ $item->subtotal ? 'Rp '.number_format($item->subtotal, 0, ',', '.') : '-' }}</small></div>@endforeach</div>
                    @else
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-zinc-500">Pilih produk batch atau tulis nama item langsung, lalu tautkan shipment group jika diperlukan.</p>
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

            <footer class="admin-form-footer"><p class="admin-form-footer-note">Buyer, GO, item, shipment, dan tagihan tersambung ke pesanan yang sama.</p><div class="admin-form-actions"><a class="admin-form-secondary" href="{{ $order->exists ? route('admin.member-orders.show', $order, false) : route('admin.member-orders.index', [], false) }}">Batal</a><button type="submit" class="admin-form-primary">Simpan Pesanan</button></div></footer>
        </form>
    </x-admin-form-shell>

    @unless($itemsAreLocked)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const container = document.querySelector('[data-items]');
                const template = document.querySelector('[data-item-template]');
                const addButton = document.querySelector('[data-add-item]');
                let nextIndex = {{ count($rows) }};
                const batchSelect = document.querySelector('select[name="batch_id"]');
                const filterProducts = () => {
                    container.querySelectorAll('[data-item-product]').forEach((select) => {
                        [...select.options].forEach((option) => {
                            if (!option.value) return;
                            option.disabled = option.dataset.batch !== batchSelect.value;
                            option.hidden = option.disabled;
                        });
                        if (select.selectedOptions[0]?.disabled) select.value = '';
                    });
                };
                window.jQuery(batchSelect).on('change', filterProducts);
                addButton?.addEventListener('click', () => {
                    container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
                    filterProducts();
                });
                container?.addEventListener('change', (event) => {
                    if (!event.target.matches('[data-item-product]')) return;
                    const option = event.target.selectedOptions[0];
                    if (!option?.value) return;
                    const row = event.target.closest('[data-item-row]');
                    row.querySelector('input[name$="[item_name]"]').value = option.dataset.name;
                    row.querySelector('input[name$="[variant]"]').value = option.dataset.variant;
                    row.querySelector('input[name$="[unit_price]"]').value = option.dataset.price;
                });
                container?.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-item]');
                    if (!button) return;
                    const rows = container.querySelectorAll('[data-item-row]');
                    if (rows.length === 1) {
                        button.closest('[data-item-row]').querySelectorAll('select').forEach((select) => select.value = '');
                        button.closest('[data-item-row]').querySelectorAll('input').forEach((input) => input.value = input.matches('[data-item-qty]') ? 1 : '');
                        return;
                    }
                    button.closest('[data-item-row]').remove();
                });
            });
        </script>
    @endunless
</x-layouts.app>
