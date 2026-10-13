<x-layouts.app title="Edit Profil Admin — Ocean Paws">
    <div class="profile-page profile-detail-page">
        <div class="profile-shell profile-edit-shell profile-detail-shell">
            <a class="profile-edit-back" href="{{ route('profile.detail') }}">← Kembali ke profil</a>
            <section class="section-card profile-edit-card">
                <x-profile-page-heading title="Profil admin" description="Kelola nama, email login, dan password akunmu." :user="$user" />
                @if(session('status'))<p class="profile-edit-success" role="status">{{ session('status') }}</p>@endif
                <form method="POST" action="{{ route('profile.admin.update') }}" class="profile-edit-form" data-profile-edit-form>
                    @csrf
                    @method('PUT')
                    <x-text-input label="Nama" name="name" :value="$user->name" autocomplete="name" maxlength="255" required />
                    <x-text-input label="Email login" name="email" type="email" :value="$user->email" autocomplete="email" maxlength="255" required />
                    <x-text-input label="Password saat ini" name="current_password" type="password" autocomplete="current-password" />
                    <p class="text-sm">Password saat ini wajib diisi jika mengganti email atau password.</p>
                    <x-text-input label="Password baru (opsional)" name="password" type="password" autocomplete="new-password" minlength="8" aria-describedby="password-requirements" />
                    <x-text-input label="Konfirmasi password baru" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" aria-describedby="password-requirements" />
                    <x-password-requirements />
                    <p class="text-sm">Kosongkan password baru untuk mempertahankan password saat ini.</p>
                    <footer><a href="{{ route('profile.detail') }}">Batal</a><button type="submit" data-profile-save>Simpan profil</button></footer>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
