<x-layouts.app title="Reset Password">
    <x-auth-shell>
        <span class="auth-kicker">PASSWORD BARU</span>
        <h1 class="auth-title">Reset password</h1>
        <p class="auth-description">Buat password baru untuk akunmu. Tautan berlaku 60 menit dan hanya bisa digunakan sekali.</p>
        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-text-input label="Email akun" name="email" type="email" :value="$email" autocomplete="email" maxlength="255" required />
            <x-text-input label="Password baru" name="password" type="password" autocomplete="new-password" minlength="8" aria-describedby="password-requirements" required />
            <x-text-input label="Konfirmasi password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" aria-describedby="password-requirements" required />
            <x-password-requirements />
            <button type="submit" class="auth-submit">Simpan password baru</button>
        </form>
        <p class="auth-switch"><a class="auth-link" href="{{ route('password.request') }}">Minta tautan reset baru</a></p>
    </x-auth-shell>
</x-layouts.app>
