<x-layouts.app :title="$group->exists ? 'Edit Group' : 'Tambah Group'">
    <x-admin-form-shell :title="$group->exists ? 'Edit Group' : 'Tambah Group'" eyebrow="Label Customer" description="Group aktif dapat dipilih saat registrasi. Group nonaktif tetap tersimpan pada customer lama." max-width="max-w-3xl">
        <form method="POST" action="{{ $group->exists ? route('admin.customer-groups.update', $group) : route('admin.customer-groups.store') }}">
            @csrf
            @if($group->exists) @method('PUT') @endif
            <div class="admin-form-body">
                <x-admin-form-section title="Label Group">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-text-input label="Nama group" name="name" :value="$group->name" maxlength="255" required />
                        <x-text-input label="Warna label" name="color" type="color" :value="$group->color ?: '#7c3aed'" required />
                        <label class="block sm:col-span-2"><span>Keterangan</span><textarea name="description" rows="3" maxlength="2000">{{ old('description', $group->description) }}</textarea></label>
                        <label class="admin-form-choice"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $group->is_active ?? true))><span><strong>Group aktif</strong><small>Tersedia untuk customer baru.</small></span></label>
                    </div>
                </x-admin-form-section>
            </div>
            <footer class="admin-form-footer"><a class="admin-form-secondary" href="{{ route('admin.customer-groups.index') }}">Batal</a><button class="admin-form-primary" type="submit">Simpan Group</button></footer>
        </form>
    </x-admin-form-shell>
</x-layouts.app>
