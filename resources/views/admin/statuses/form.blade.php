@php
    $formTitle = $orderStatus->exists ? 'Edit Status' : 'Tambah Status';
    $scopeDefinitions = [
        'all' => ['label' => 'Status Tracking', 'form_hint' => 'perjalanan batch dan pesanan'],
        'payment' => ['label' => 'Status Pembayaran', 'form_hint' => 'pembayaran customer'],
    ];
    if ($orderStatus->exists && !in_array($orderStatus->scope, ['all', 'payment'], true)) {
        $scopeDefinitions[$orderStatus->scope] = ['label' => 'Status Tracking', 'form_hint' => 'pertahankan penggunaan data lama'];
    }
@endphp

<x-layouts.app :title="$formTitle">
    <x-admin-form-shell
        :title="$formTitle"
        eyebrow="Manajemen Status"
        description="Susun status tracking perjalanan atau status pembayaran. Label group dikelola melalui menu Group."
        max-width="max-w-4xl"
    >
        <form method="POST" action="{{ $orderStatus->exists ? route('admin.order-statuses.update', $orderStatus, false) : route('admin.order-statuses.store', [], false) }}">
            @csrf
            @if ($orderStatus->exists) @method('PUT') @endif

            <div class="admin-form-body">
                <x-admin-form-intro
                    title="Panduan Status"
                    description="Gunakan nama yang mudah dipahami pelanggan. Kode sistem dibuat otomatis dan dijaga tetap stabil sebagai referensi internal."
                />

                <x-admin-form-section title="Identitas dan Tampilan Status">
                    <div class="status-form-primary-row grid gap-4 lg:grid-cols-3">
                        <x-text-input label="Nama status" name="name" :value="$orderStatus->name" placeholder="Contoh: Tiba di Gudang Admin" required />
                        <x-text-input label="Warna badge" name="color" type="color" :value="$orderStatus->color ?: '#7c3aed'" required />
                        <label class="block">
                            <span>Jenis hasil</span>
                            <select name="status_type" required>
                                @foreach (['process' => 'Sedang diproses', 'success' => 'Berhasil / selesai', 'failed' => 'Bermasalah', 'cancelled' => 'Dibatalkan'] as $type => $label)
                                    <option value="{{ $type }}" @selected(old('status_type', $orderStatus->status_type ?: 'process') === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Cakupan dan Keterangan Status">
                    <div class="grid gap-4">
                        <label class="block">
                            <span>Berlaku untuk</span>
                            <select name="scope">
                                @foreach ($scopeDefinitions as $scope => $definition)
                                    <option value="{{ $scope }}" @selected(old('scope', $orderStatus->scope ?: (request('scope') === 'payment' ? 'payment' : 'all')) === $scope)>{{ $definition['label'] }} — {{ $definition['form_hint'] }}</option>
                                @endforeach
                            </select>
                            <small class="admin-form-help">Cakupan menentukan lokasi status dapat dipilih oleh admin.</small>
                        </label>
                        <label class="block">
                            <span>Keterangan yang mudah dipahami pelanggan</span>
                            <textarea name="description" rows="3" placeholder="Jelaskan apa yang sedang terjadi dan langkah berikutnya">{{ old('description', $orderStatus->description) }}</textarea>
                        </label>
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Perilaku Status">
                    <div class="grid gap-3 md:grid-cols-4">
                        <label class="admin-form-choice">
                            <input type="checkbox" name="is_initial" value="1" @checked(old('is_initial', $orderStatus->is_initial))>
                            <span><strong>Status awal</strong><small>Tahap pertama pada cakupan ini.</small></span>
                        </label>
                        <label class="admin-form-choice">
                            <input type="checkbox" name="is_final" value="1" @checked(old('is_final', $orderStatus->is_final))>
                            <span><strong>Status final</strong><small>Tidak ada progress lanjutan.</small></span>
                        </label>
                        <label class="admin-form-choice">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $orderStatus->is_active ?? true))>
                            <span><strong>Status aktif</strong><small>Tersedia pada dropdown admin.</small></span>
                        </label>
                        <label class="admin-form-choice" data-status-order-lock>
                            <input type="checkbox" name="locks_order_editing" value="1" @checked(old('locks_order_editing', $orderStatus->locks_order_editing))>
                            <span><strong>Kunci perubahan pesanan</strong><small>Saat status dipakai pada batch, pelanggan, batch, dan produk pesanan tidak dapat diubah.</small></span>
                        </label>
                    </div>
                </x-admin-form-section>
            </div>

            <footer class="admin-form-footer">
                <p class="admin-form-footer-note">Perubahan status dapat memengaruhi pilihan progress pada batch dan pesanan.</p>
                <div class="admin-form-actions">
                    <a class="admin-form-secondary" href="{{ $orderStatus->exists ? route('admin.order-statuses.show', $orderStatus, false) : route('admin.order-statuses.index', [], false) }}">Batal</a>
                    <button type="submit" class="admin-form-primary">Simpan Status</button>
                </div>
            </footer>
        </form>
    </x-admin-form-shell>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const scope = document.querySelector('[name="scope"]');
            const orderLockChoice = document.querySelector('[data-status-order-lock]');
            const orderLockInput = orderLockChoice.querySelector('input');

            const syncOrderLock = () => {
                const isBatchProgress = ['batch', 'all'].includes(scope.value);
                orderLockChoice.hidden = !isBatchProgress;
                orderLockInput.disabled = !isBatchProgress;
                if (!isBatchProgress) orderLockInput.checked = false;
            };

            scope.addEventListener('change', syncOrderLock);
            syncOrderLock();
        });
    </script>
</x-layouts.app>

