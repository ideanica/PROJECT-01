<x-layouts.app title="Login">
    <x-auth-shell>
        <span class="auth-kicker">WELCOME BACK</span>
        <h1 class="auth-title">Login</h1>
        <p class="auth-description">Masuk ke akun Ocean Paws.</p>
        <form method="POST" action="{{ route('login') }}" class="auth-form auth-form-login">
            @csrf
            <x-text-input label="Username LINE atau email" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus />
            <x-text-input label="Password" name="password" type="password" autocomplete="current-password" required />
            <a class="auth-link text-right" href="{{ route('password.request') }}">Lupa password?</a>
            <label class="auth-remember">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                Ingat sesi ini
            </label>
            <button type="submit" class="auth-submit">Masuk</button>
        </form>
        <p class="auth-switch">Belum punya akun? <a class="auth-link" href="{{ route('register') }}">Daftar terlebih dahulu</a></p>
    </x-auth-shell>
    @include('auth.partials.card-styles')
</x-layouts.app>
