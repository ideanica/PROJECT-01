@php
    $formTitle = $batch->exists ? 'Edit Batch' : 'Tambah Batch';
    $progressIsLocked = $batch->exists && $batch->progress_locked;
    $variantRows = old('variants', $batch->products->map(fn ($product) => [
        'product_id' => $product->id,
        'name' => $product->variant ?: $product->name,
        'full_price' => $product->pivot->full_price ?: $product->default_price,
        'is_available' => $product->pivot->is_available,
    ])->values()->all());

    // Determine payment method for this batch
    $batchPaymentMethod = $batch->getEffectivePaymentMethod();
    $hasPaymentMethod = $batch->getHasPaymentMethodAttribute();
@endphp

<x-layouts.app :title="$formTitle">
    <x-admin-form-shell
        :title="$formTitle"
        eyebrow="Batch Pembelian"
        description="Buat wadah progress untuk sekumpulan pesanan. Pelanggan dan item ditambahkan dari detail batch."
        max-width="max-w-4xl"
        class="mx-auto [&_.admin-form-eyebrow]:text-[#2f6b46]! [&_.admin-form-intro]:border-[#c3d9ca]! [&_.admin-form-intro]:bg-[#eef4ef]! [&_.admin-form-intro]:bg-none! [&_.admin-form-intro-icon]:text-[#2f6b46]! [&_.admin-form-intro_h2]:text-[#275a3a]! [&_.admin-form-secondary:hover]:border-[#c3d9ca]! [&_.admin-form-secondary:hover]:text-[#275a3a]! [&_input:focus]:border-[#2f6b46]! [&_select:focus]:border-[#2f6b46]! [&_textarea:focus]:border-[#2f6b46]! [&_input:focus]:shadow-[0_0_0_3px_rgba(47,107,70,0.12)]! [&_select:focus]:shadow-[0_0_0_3px_rgba(47,107,70,0.12)]! [&_textarea:focus]:shadow-[0_0_0_3px_rgba(47,107,70,0.12)]!"
    >
        <form method="POST" enctype="multipart/form-data" action="{{ $batch->exists ? route('admin.batches.update', $batch, false) : route('admin.batches.store', [], false) }}">
            @csrf
            @if ($batch->exists) @method('PUT') @endif

            <div class="admin-form-body">
                <x-admin-form-intro
                    title="Batch sebagai Pusat Progress"
                    description="Setiap pesanan di dalam batch mengikuti status utama batch, kecuali admin memberi status khusus pada pesanan tersebut."
                />

                <x-admin-form-section title="Identitas Batch">
                    <div class="batch-form-identity">
                        <div class="batch-form-identity-code">
                            <span>{{ $batch->exists ? 'Nomor batch' : 'Nomor batch otomatis' }}</span>
                            <strong>{{ $batch->exists ? $batch->batch_number : 'BTH-'.now()->format('ym').'-XXXX' }}</strong>
                        </div>
                        <x-text-input label="Nama batch" name="batch_name" :value="$batch->batch_name" placeholder="Contoh: NCT DREAM Photocard Batch" />
                        <small class="admin-form-help">Nomor batch dibuat otomatis. Produk tidak perlu disiapkan di sini karena item dicatat langsung pada setiap pesanan.</small>
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Katalog Landing Page">
                    <div
                        class="admin-photo-uploader"
                        data-admin-photo-uploader
                        data-has-existing="{{ $batch->catalog_image_path ? '1' : '0' }}"
                    >
                        <div class="admin-photo-uploader-head">
                            <div><strong>Foto katalog batch</strong><small>Foto utama yang tampil pada kartu dan detail katalog.</small></div>
                            <span data-photo-state>{{ $batch->catalog_image_path ? 'Tersimpan' : 'Belum ada foto' }}</span>
                        </div>

                        <div class="admin-photo-preview" data-photo-preview>
                            @if($batch->catalog_image_path)
                                <img src="{{ $batch->catalog_image_url }}" alt="Gambar katalog {{ $batch->batch_name }}" data-photo-image>
                            @else
                                <img src="" alt="Preview foto batch" data-photo-image hidden>
                            @endif
                            <div class="admin-photo-placeholder" data-photo-placeholder @if($batch->catalog_image_path) hidden @endif>
                                <i class="bi bi-images" aria-hidden="true"></i>
                                <strong>Pilih foto batch</strong>
                                <small>Preview akan muncul sebelum batch disimpan</small>
                            </div>
                            <button type="button" class="admin-photo-remove" data-photo-remove @if(!$batch->catalog_image_path) hidden @endif aria-label="Batalkan atau hapus foto" title="Batalkan atau hapus foto">&times;</button>
                        </div>

                        <div class="admin-photo-actions">
                            <label for="catalog-image-input" class="admin-photo-choose inline-flex! w-auto!"><i class="bi bi-upload" aria-hidden="true"></i><span data-photo-button-label>{{ $batch->catalog_image_path ? ' Ganti foto' : ' Pilih foto' }}</span></label>
                            <span data-photo-file-name>{{ $batch->catalog_image_path ? basename($batch->catalog_image_path) : 'Belum ada file dipilih' }}</span>
                        </div>
                        <input id="catalog-image-input" class="admin-photo-native-input" type="file" name="catalog_image" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" data-photo-input>
                        <input type="hidden" name="remove_catalog_image" value="0" data-photo-remove-input>
                        <p class="admin-photo-error" data-photo-error hidden></p>
                        <small class="admin-form-help">JPG/JPEG, PNG, atau WEBP. Maksimal 4 MB. Foto lama tetap aman sampai perubahan disimpan.</small>
                        @error('catalog_image')<p class="admin-photo-server-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4 grid items-start gap-4 sm:grid-cols-2">
                        <x-text-input label="Batas pemesanan" name="ordering_deadline" type="datetime-local" :value="$batch->ordering_deadline?->format('Y-m-d\TH:i')" />
                        <label class="admin-form-choice">
                            <input type="hidden" name="is_catalog_visible" value="0">
                            <input type="checkbox" name="is_catalog_visible" value="1" @checked(old('is_catalog_visible', $batch->is_catalog_visible))>
                            <span><strong>Tampilkan di katalog</strong><small>Batch akan muncul pada landing page meskipun gambar masih belum ditambahkan.</small></span>
                        </label>
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Pajak EMS per Batch">
                    @include('admin.batches.partials.ems-tax-fields')
                </x-admin-form-section>

                <x-admin-form-section title="Pembayaran">
                    @if($batchPaymentMethod)
                        <div class="admin-payment-method-card">
                            <div class="admin-payment-method-header">
                                <div class="admin-payment-method-icon">
                                    @if($batchPaymentMethod->type === 'qris')
                                        <i class="bi bi-qr-code" aria-hidden="true"></i>
                                    @else
                                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <div>
                                    <strong>{{ $batchPaymentMethod->name }}</strong>
                                    <span class="admin-payment-method-badge admin-payment-method-badge-success">Aktif</span>
                                </div>
                            </div>
                            @if($batchPaymentMethod->image_path)
                                <div class="admin-payment-method-preview">
                                    <img src="{{ $batchPaymentMethod->image_url }}" alt="{{ $batchPaymentMethod->name }}" style="max-width: 200px; max-height: 200px;">
                                </div>
                            @endif
                            @if($batchPaymentMethod->instructions)
                                <p class="admin-payment-method-instructions">{{ $batchPaymentMethod->instructions }}</p>
                            @endif
                            <small class="admin-form-help">
                                Pembayaran untuk batch ini akan menggunakan payment method aktif.
                                <a href="{{ route('admin.batches.settings') }}" class="text-[#2f6b46]! hover:underline">[Kelola]</a>
                            </small>
                        </div>
                    @else
                        <div class="admin-payment-method-warning">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <div>
                                <strong>Belum ada metode pembayaran aktif.</strong>
                                <p>Atur QRIS terlebih dahulu di <a href="{{ route('admin.batches.settings') }}" class="text-[#2f6b46]! hover:underline">Pengaturan Batch</a>.</p>
                            </div>
                        </div>
                    @endif

                    <input type="hidden" name="payment_method_id" value="{{ old('payment_method_id', $batch->payment_method_id) }}">
                </x-admin-form-section>

                <x-admin-form-section title="Variasi Barang">
                    <div class="admin-variant-builder" data-admin-variant-builder>
                        <div class="admin-variant-builder-head">
                            <div>
                                <strong>Pilihan yang dapat dipesan pelanggan</strong>
                                <small>DP dihitung otomatis sebesar {{ number_format($dpPercentage, 2, ',', '.') }}% dari harga lunas.</small>
                            </div>
                            <button type="button" class="admin-form-inline-action" data-add-admin-variant>+ Tambah variasi</button>
                        </div>

                        <div class="admin-variant-list" data-admin-variant-list>
                            @foreach($variantRows as $index => $variant)
                                <div class="admin-variant-row" data-admin-variant-row>
                                    <input type="hidden" name="variants[{{ $index }}][product_id]" value="{{ $variant['product_id'] ?? '' }}">
                                    <label><span>Nama variasi</span><input name="variants[{{ $index }}][name]" value="{{ $variant['name'] ?? '' }}" placeholder="Contoh: ALT Ver." required></label>
                                    <label><span>Harga lunas</span><input type="number" min="0" step="1" name="variants[{{ $index }}][full_price]" value="{{ $variant['full_price'] ?? '' }}" placeholder="257400" required></label>
                                    <label class="admin-variant-available flex! w-auto!">
                                        <input type="hidden" name="variants[{{ $index }}][is_available]" value="0">
                                        <input type="checkbox" name="variants[{{ $index }}][is_available]" value="1" @checked((bool) ($variant['is_available'] ?? true))>
                                        <span>Tersedia</span>
                                    </label>
                                    <button type="button" class="admin-variant-remove" data-remove-admin-variant aria-label="Hapus variasi"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                </div>
                            @endforeach
                        </div>

                        <div class="admin-variant-empty" data-admin-variant-empty @if(count($variantRows)) hidden @endif>
                            Belum ada variasi. Batch dapat disimpan sekarang dan variasi ditambahkan menyusul.
                        </div>

                        <template data-admin-variant-template>
                            <div class="admin-variant-row" data-admin-variant-row>
                                <input type="hidden" name="variants[__INDEX__][product_id]" value="">
                                <label><span>Nama variasi</span><input name="variants[__INDEX__][name]" placeholder="Contoh: ALT Ver." required></label>
                                <label><span>Harga lunas</span><input type="number" min="0" step="1" name="variants[__INDEX__][full_price]" placeholder="257400" required></label>
                                <label class="admin-variant-available flex! w-auto!">
                                    <input type="hidden" name="variants[__INDEX__][is_available]" value="0">
                                    <input type="checkbox" name="variants[__INDEX__][is_available]" value="1" checked>
                                    <span>Tersedia</span>
                                </label>
                                <button type="button" class="admin-variant-remove" data-remove-admin-variant aria-label="Hapus variasi"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                            </div>
                        </template>
                    </div>
                </x-admin-form-section>

                <x-admin-form-section title="Progress dan Periode">
                    <div class="grid gap-4">
                        @if($progressIsLocked)
                            <div class="admin-form-readonly">
                                <span>Status batch</span>
                                <strong>{{ $batch->currentStatus?->name ?: 'Status final' }}</strong>
                                <small class="admin-form-help">Progress final tidak dapat diubah.</small>
                                <input type="hidden" name="current_status_id" value="{{ $batch->current_status_id }}">
                            </div>
                        @else
                            <label class="block">
                                <span>Status batch</span>
                                <select name="current_status_id">
                                    <option value="">Belum ada status</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->id }}" @selected(old('current_status_id', $batch->current_status_id) == $status->id)>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <div class="grid items-start gap-4 sm:grid-cols-2">
                            <x-text-input label="Tanggal mulai" name="started_at" type="datetime-local" :value="$batch->started_at?->format('Y-m-d\TH:i')" />
                            <x-text-input label="Tanggal selesai" name="completed_at" type="datetime-local" :value="$batch->completed_at?->format('Y-m-d\TH:i')" />
                        </div>
                    </div>
                    @if($batch->exists && !$progressIsLocked)
                        <label class="mt-4 block">
                            <span>Catatan perubahan status</span>
                            <input name="status_note" value="{{ old('status_note') }}" placeholder="Opsional, jelaskan perubahan progress">
                        </label>
                    @endif
                </x-admin-form-section>

                <x-admin-form-section title="Informasi Batch">
                    <label class="block"><span>Deskripsi</span><textarea name="description" rows="3" placeholder="Deskripsi singkat batch">{{ old('description', $batch->description) }}</textarea></label>
                    <label class="mt-4 block"><span>Catatan admin</span><textarea name="notes" rows="4" placeholder="Informasi penting mengenai batch">{{ old('notes', $batch->notes) }}</textarea></label>
                </x-admin-form-section>

                @if($batch->exists)
                    <x-admin-form-section title="Pengarsipan">
                        <label class="admin-form-choice">
                            <input type="checkbox" name="is_archived" value="1" @checked(old('is_archived', $batch->is_archived))>
                            <span><strong>Arsipkan batch</strong><small>Batch tetap tersimpan sebagai riwayat dan tidak tampil di daftar aktif.</small></span>
                        </label>
                    </x-admin-form-section>
                @endif
            </div>

            <footer class="admin-form-footer">
                <p class="admin-form-footer-note">Setelah batch disimpan, tambahkan pesanan langsung dari halaman detail batch.</p>
                <div class="admin-form-actions">
                    <a class="admin-form-secondary" href="{{ $batch->exists ? route('admin.batches.show', $batch, false) : route('admin.batches.index', [], false) }}">Batal</a>
                    <button type="submit" class="admin-form-primary">Simpan Batch</button>
                </div>
            </footer>
        </form>
    </x-admin-form-shell>
</x-layouts.app>
