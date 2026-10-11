<x-layouts.app title="Lupa Password">
    <x-auth-shell>
        <span class="auth-kicker">PEMULIHAN AKUN</span>
        <h1 class="auth-title">Lupa password?</h1>
        <p class="auth-description">Masukkan email akun customer atau admin. Kami akan mengirim tautan untuk membuat password baru.</p>
        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <x-text-input label="Email akun" name="email" type="email" autocomplete="email" maxlength="255" required autofocus />
            <button type="submit" class="auth-submit">Kirim tautan reset</button>
        </form>
        <p class="auth-switch"><a class="auth-link" href="{{ route('login') }}">Kembali ke login</a></p>
    </x-auth-shell>
</x-layouts.app>
