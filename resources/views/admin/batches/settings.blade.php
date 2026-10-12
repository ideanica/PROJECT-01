<x-layouts.app title="Pengaturan Batch">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Pengaturan Batch" description="Pengaturan reusable terkait operasional Batch."></x-page-heading>
    </div>

    <article class="detail-record-card before:bg-none! [&_.detail-record-hero]:bg-[linear-gradient(135deg,#ffffff,#f6faf6_58%,#eef6ef)]!">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker text-[#275a3a]!">Pengaturan Batch</p>
                <h2 class="detail-record-title">Pembayaran</h2>
                <p class="detail-record-description">Kelola QRIS dan metode pembayaran yang digunakan oleh seluruh Batch.</p>
            </div>
        </header>

        @if(session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
        @endif

        <section class="detail-record-section">
            <div class="detail-record-section-heading">
                <div>
                    <h3>QRIS / Payment Method</h3>
                    <p>Metode pembayaran yang aktif akan digunakan otomatis oleh Batch baru.</p>
                </div>
                <button type="button" class="admin-form-inline-action" data-status-modal-open="add-payment-method">+ Tambah Payment Method</button>
            </div>

            @if($paymentMethods->isEmpty())
                <div class="order-special-status-empty">
                    <p>Belum ada payment method. Tambahkan QRIS pertama untuk memulai.</p>
                </div>
            @else
                <div class="payment-methods-grid">
                    @foreach($paymentMethods as $method)
                        <article class="payment-method-card {{ $method->is_active ? 'is-active' : '' }}">
                            @if($method->is_active)
                                <span class="payment-method-active-badge">AKTIF</span>
                            @endif

                            <div class="payment-method-image">
                                @if($method->image_path)
                                    <img src="{{ $method->image_url }}" alt="{{ $method->name }}">
                                @else
                                    <div class="payment-method-placeholder">
                                        <i class="bi bi-qr-code" aria-hidden="true"></i>
                                        <small>Tidak ada gambar</small>
                                    </div>
                                @endif
                            </div>

                            <div class="payment-method-info">
                                <h4>{{ $method->name }}</h4>
                                <span class="payment-method-type">{{ strtoupper($method->type) }}</span>
                                @if($method->account_name)
                                    <p class="payment-method-account">{{ $method->account_name }}</p>
                                @endif
                                @if($method->instructions)
                                    <p class="payment-method-instructions">{{ $method->instructions }}</p>
                                @endif
                            </div>

                            <div class="payment-method-actions">
                                <form method="POST" action="{{ route('admin.batches.payment-methods.toggle', $method) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="is_active" value="{{ $method->is_active ? '0' : '1' }}">
                                    <button type="submit" class="admin-form-toggle {{ $method->is_active ? 'bg-emerald-500' : 'bg-zinc-300' }}" aria-label="{{ $method->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <span class="toggle-thumb"></span>
                                    </button>
                                </form>

                                <button type="button" class="admin-form-secondary" data-status-modal-open="edit-payment-method" data-payment-method='{{ $method->toJson() }}'>Edit</button>

                                <form method="POST" action="{{ route('admin.batches.payment-methods.destroy', $method) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus? Hanya bisa dihapus jika tidak digunakan batch manapun.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-form-danger">Hapus</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </article>

    <!-- Add Payment Method Modal -->
    <div class="status-modal" data-status-modal="add-payment-method" role="dialog" aria-modal="true" aria-labelledby="add-payment-method-title" aria-hidden="true" hidden>
        <button type="button" class="status-modal-backdrop" data-status-modal-close aria-label="Tutup modal"></button>
        <div class="status-modal-surface order-status-modal-surface" tabindex="-1">
            <header class="status-modal-header">
                <div>
                    <p>Payment Method</p>
                    <h2 id="add-payment-method-title">Tambah Payment Method</h2>
                </div>
                <button type="button" class="status-modal-close" data-status-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>

            <div class="status-modal-body">
                <form method="POST" action="{{ route('admin.batches.payment-methods.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label>
                        <span>Nama <span class="text-red-500" aria-hidden="true">*</span></span>
                        <input type="text" name="name" required placeholder="Contoh: QRIS OceanPaws" maxlength="255">
                    </label>
                    <label>
                        <span>Tipe <span class="text-red-500" aria-hidden="true">*</span></span>
                        <select name="type" required>
                            <option value="qris" selected>QRIS</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </label>
                    <label>
                        <span>Gambar QR <span class="text-red-500" aria-hidden="true">*</span></span>
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" required>
                        <small class="admin-form-help">Format: JPG, JPEG, PNG, WEBP. Maksimal 4 MB.</small>
                    </label>
                    <label>
                        <span>Nama Rekening</span>
                        <input type="text" name="account_name" placeholder="Contoh: Ocean Paws" maxlength="255">
                    </label>
                    <label>
                        <span>Instruksi Pembayaran</span>
                        <textarea name="instructions" rows="3" maxlength="2000" placeholder="Contoh: Scan QRIS dan transfer sesuai nominal tagihan."></textarea>
                    </label>
                    <label class="admin-form-choice">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span><strong>Jadikan aktif</strong><small>Hanya satu payment method per tipe yang bisa aktif.</small></span>
                    </label>
                    <div class="flex justify-end">
                        <button class="admin-form-primary" type="submit">Simpan Payment Method</button>
                    </div>
                </form>
            </div>

            <footer class="status-detail-footer status-modal-footer">
                <p>QRIS lama tidak akan dihapus secara destruktif. Nonaktifkan saja jika tidak ingin dipakai lagi.</p>
                <button type="button" class="admin-form-secondary" data-status-modal-close>Tutup</button>
            </footer>
        </div>
    </div>

    <!-- Edit Payment Method Modal -->
    <div class="status-modal" data-status-modal="edit-payment-method" role="dialog" aria-modal="true" aria-labelledby="edit-payment-method-title" aria-hidden="true" hidden>
        <button type="button" class="status-modal-backdrop" data-status-modal-close aria-label="Tutup modal"></button>
        <div class="status-modal-surface order-status-modal-surface" tabindex="-1">
            <header class="status-modal-header">
                <div>
                    <p>Payment Method</p>
                    <h2 id="edit-payment-method-title">Edit Payment Method</h2>
                </div>
                <button type="button" class="status-modal-close" data-status-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </header>

            <div class="status-modal-body">
                <form method="POST" action="" enctype="multipart/form-data" id="edit-payment-method-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id" id="edit-pm-id">
                    <label>
                        <span>Nama <span class="text-red-500" aria-hidden="true">*</span></span>
                        <input type="text" name="name" id="edit-pm-name" required placeholder="Contoh: QRIS OceanPaws" maxlength="255">
                    </label>
                    <label>
                        <span>Tipe <span class="text-red-500" aria-hidden="true">*</span></span>
                        <select name="type" id="edit-pm-type" required>
                            <option value="qris">QRIS</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </label>
                    <label>
                        <span>Gambar QR (kosongkan jika tidak ingin mengubah)</span>
                        <input type="file" name="image" id="edit-pm-image" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                        <small class="admin-form-help">Format: JPG, JPEG, PNG, WEBP. Maksimal 4 MB. Gambar lama tidak akan dihapus.</small>
                    </label>
                    <label>
                        <span>Nama Rekening</span>
                        <input type="text" name="account_name" id="edit-pm-account_name" placeholder="Contoh: Ocean Paws" maxlength="255">
                    </label>
                    <label>
                        <span>Instruksi Pembayaran</span>
                        <textarea name="instructions" id="edit-pm-instructions" rows="3" maxlength="2000" placeholder="Contoh: Scan QRIS dan transfer sesuai nominal tagihan."></textarea>
                    </label>
                    <label class="admin-form-choice">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="edit-pm-is_active">
                        <span><strong>Jadikan aktif</strong><small>Hanya satu payment method per tipe yang bisa aktif.</small></span>
                    </label>
                    <div class="flex justify-end">
                        <button class="admin-form-primary" type="submit">Simpan Perubahan</button>
                    </div>
                </form>
            </div>

            <footer class="status-detail-footer status-modal-footer">
                <p>Gambar lama tidak akan dihapus secara destruktif.</p>
                <button type="button" class="admin-form-secondary" data-status-modal-close>Tutup</button>
            </footer>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Handle edit modal
            document.querySelectorAll('[data-status-modal-open="edit-payment-method"]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const data = JSON.parse(btn.dataset.paymentMethod);
                    document.getElementById('edit-pm-id').value = data.id;
                    document.getElementById('edit-pm-name').value = data.name;
                    document.getElementById('edit-pm-type').value = data.type;
                    document.getElementById('edit-pm-account_name').value = data.account_name || '';
                    document.getElementById('edit-pm-instructions').value = data.instructions || '';
                    document.getElementById('edit-pm-is_active').checked = data.is_active;
                    document.getElementById('edit-payment-method-form').action = `/admin/batches/settings/payment-methods/${data.id}`;
                    document.querySelector('[data-status-modal="edit-payment-method"]').hidden = false;
                });
            });
        });
    </script>
</x-layouts.app>
