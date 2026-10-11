<x-layouts.app title="Edit Profil — Ocean Paws">
    <div class="profile-page profile-detail-page">
        <div class="profile-shell profile-edit-shell profile-detail-shell">
            <a class="profile-edit-back" href="{{ route('profile.detail') }}">← Kembali ke profil</a>
            <section class="section-card profile-edit-card profile-edit-photo-card">
                <x-profile-page-heading title="Profil customer" description="Lengkapi identitas dan kontak agar admin dapat memproses pesananmu." :member="$member" :user="$user" :photo-preview="true" />
                @if(session('status'))<p class="profile-edit-success" role="status">{{ session('status') }}</p>@endif
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="profile-edit-form" data-profile-edit-form>
                    @csrf
                    @method('PUT')
                    <section id="foto-profile" class="profile-photo-editor" data-profile-photo-editor>
                        <div class="profile-photo-controls">
                            <label for="profile-photo">Foto profil</label>
                            <input id="profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="profile-photo-hint" data-photo-input>
                            <p id="profile-photo-hint">JPG, PNG, atau WebP. Maksimal 2 MB dan 4096 × 4096 piksel. Foto disimpan setelah menekan Simpan profil.</p>
                            @error('profile_photo')<small class="text-red-700" role="alert">{{ $message }}</small>@enderror
                            <p class="text-red-700" data-photo-error role="alert" hidden></p>
                            @if($member->avatar_url)
                                <label class="profile-photo-remove"><input type="checkbox" name="remove_photo" value="1" data-photo-remove @checked(old('remove_photo'))> Hapus foto profil</label>
                            @endif
                        </div>
                    </section>
                    <div class="profile-edit-grid">
                        <x-text-input label="Nama lengkap / penerima" name="display_name" :value="$member->display_name" autocomplete="name" maxlength="255" required />
                        <x-text-input label="Username LINE" name="username" :value="$member->username" autocomplete="username" minlength="3" maxlength="50" pattern="[a-zA-Z0-9][a-zA-Z0-9._-]*" required />
                        <x-text-input label="Email" name="email" type="email" :value="$member->email" autocomplete="email" maxlength="255" required />
                        <x-text-input label="Nomor telepon" name="phone" type="tel" :value="$member->phone" autocomplete="tel" maxlength="30" placeholder="Contoh: 081234567890" />
                    </div>
                    <p class="text-sm">Username LINE harus unik dan digunakan untuk login. Perubahan username LINE juga mengubah username login akunmu.</p>
                    <label class="profile-edit-address"><span>Alamat lengkap</span><textarea name="address" rows="5" maxlength="2000" autocomplete="street-address" aria-describedby="profile-address-warning" placeholder="Jalan, nomor rumah, kecamatan, kota, provinsi, dan kode pos">{{ old('address', $member->address) }}</textarea>@error('address')<small class="text-red-700">{{ $message }}</small>@enderror</label>
                    <aside id="profile-address-warning" class="profile-edit-warning">
                        <strong>{{ $shippingCount ? $shippingCount.' pesanan sedang dalam pengiriman' : 'Perubahan alamat pengiriman' }}</strong>
                        <p>Mengubah alamat profil tidak otomatis mengganti alamat pesanan yang sedang dikirim. Hubungi admin untuk mengonfirmasi apakah tujuan pengiriman masih dapat diubah.</p>
                    </aside>
                    <footer><a href="{{ route('profile.detail') }}">Batal</a><button type="submit" data-profile-save>Simpan profil</button></footer>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
