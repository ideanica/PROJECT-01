<x-layouts.app title="Daftar Customer">
    <x-auth-shell register>
        <span class="auth-kicker">JOIN THE PAWS</span>
        <h1 class="auth-title">Daftar Customer</h1>
        <p class="auth-description">Buat akun untuk memantau pesanan dan pembayaran.</p>
            <form method="POST" action="{{ route('register') }}" class="auth-form auth-form-register">
                @csrf
                <x-text-input label="Nama" name="name" autocomplete="name" maxlength="255" required autofocus />
                <x-text-input label="Username LINE" name="username" autocomplete="username" minlength="3" maxlength="50" pattern="[a-zA-Z0-9][a-zA-Z0-9._-]*" required />
                <p class="auth-hint">Username LINE harus unik. Gunakan huruf, angka, titik, garis bawah, atau tanda hubung. Username LINE disimpan dalam huruf kecil.</p>
                <x-text-input label="Email" name="email" type="email" autocomplete="email" maxlength="255" required />
                <label class="auth-group"><span class="text-sm font-semibold">Group</span>
                    <select name="customer_group_id" class="auth-select">
                        <option value="">Belum memilih</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('customer_group_id') === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_group_id')<small class="auth-field-error">{{ $message }}</small>@enderror
                </label>
                <p class="auth-hint">Pilih “Belum memilih” jika belum tahu groupmu. Admin akan menentukan group dan menyetujui akun sebelum kamu bisa login.</p>
                <x-text-input label="Password" name="password" type="password" autocomplete="new-password" minlength="8" aria-describedby="password-requirements" required />
                <x-text-input label="Konfirmasi password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" aria-describedby="password-requirements" required />
                <x-password-requirements />
                <button type="submit" class="auth-submit">Daftar akun</button>
            </form>
        <p class="auth-switch">Sudah punya akun? <a class="auth-link" href="{{ route('login') }}">Login</a></p>
    </x-auth-shell>
</x-layouts.app>
