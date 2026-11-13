<x-layouts.app :title="$group->exists ? 'Edit Group' : 'Tambah Group'">
    <x-admin-form-shell
        :title="$group->exists ? 'Edit Group' : 'Tambah Group'"
        eyebrow="Label Customer"
        description="Group aktif dapat dipilih saat registrasi. Group nonaktif tetap tersimpan pada customer lama."
        max-width="max-w-3xl"
        class="mx-auto [&_.admin-form-eyebrow]:text-[#2f6b46]! [&_.admin-form-intro]:border-[#c3d9ca]! [&_.admin-form-intro]:bg-[#eef4ef]! [&_.admin-form-intro]:bg-none! [&_.admin-form-intro-icon]:text-[#2f6b46]! [&_.admin-form-intro_h2]:text-[#275a3a]! [&_.admin-form-secondary:hover]:border-[#c3d9ca]! [&_.admin-form-secondary:hover]:text-[#275a3a]! [&_input:focus]:border-[#2f6b46]! [&_select:focus]:border-[#2f6b46]! [&_textarea:focus]:border-[#2f6b46]! [&_input:focus]:shadow-[0_0_0_3px_rgba(47,107,70,0.12)]! [&_select:focus]:shadow-[0_0_0_3px_rgba(47,107,70,0.12)]! [&_textarea:focus]:shadow-[0_0_0_3px_rgba(47,107,70,0.12)]!"
    >
        <form method="POST" action="{{ $group->exists ? route('admin.customer-groups.update', $group) : route('admin.customer-groups.store') }}">
            @csrf
            @if($group->exists) @method('PUT') @endif

            <div class="admin-form-body">
                <x-admin-form-intro
                    title="Panduan Label Group"
                    description="Label group membantu admin mengelompokkan customer. Group tidak menentukan hak akses akun dan tidak mengubah riwayat pesanan."
                />

                <x-admin-form-section title="Label Group">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-text-input label="Nama group" name="name" :value="$group->name" maxlength="255" placeholder="Contoh: Pelanggan Reguler" required />
                        <x-text-input label="Warna label" name="color" type="color" :value="$group->color ?: '#7c3aed'" required />
                        <label class="block sm:col-span-2"><span>Keterangan</span><textarea name="description" rows="3" maxlength="2000" placeholder="Opsional, jelaskan penggunaan group ini">{{ old('description', $group->description) }}</textarea>
                            @error('description')<small class="mt-1 block text-sm text-red-700">{{ $message }}</small>@enderror
                        </label>
                        <label class="admin-form-choice"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))><span><strong>Group aktif</strong><small>Tersedia untuk customer baru.</small></span></label>
                    </div>
                </x-admin-form-section>
            </div>

            <footer class="admin-form-footer">
                <p class="admin-form-footer-note">Group yang dinonaktifkan tidak muncul pada pilihan registrasi, tetapi tetap menempel pada customer lama.</p>
                <div class="admin-form-actions">
                    <a class="admin-form-secondary" href="{{ $group->exists ? route('admin.customer-groups.show', $group) : route('admin.customer-groups.index') }}">Batal</a>
                    <button class="admin-form-primary" type="submit">Simpan Group</button>
                </div>
            </footer>
        </form>
    </x-admin-form-shell>
</x-layouts.app>
