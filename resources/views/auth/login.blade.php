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
    <style>
        @media (max-width: 1023px) {
            .ocean-auth:has(.auth-form-login) .customer-public-shell {
                background: transparent;
            }
            .ocean-auth:has(.auth-form-login) .customer-public-shell > main {
                width: 90.43%;
                max-width: 1180px;
                padding: 8px 0 40px;
            }
            .ocean-auth:has(.auth-form-login) .auth-shell {
                width: 100%;
                max-width: none;
                padding: 0;
            }
            .ocean-auth:has(.auth-form-login) .auth-card {
                border: 2.5px solid #355a45;
                border-radius: 17px;
                padding: clamp(20px, 5vw, 28px);
                background: #fbd7e3;
                box-shadow: var(--customer-shadow-card, -6px 6px 0) #8aaf91;
            }
            .ocean-auth:has(.auth-form-login) .auth-kicker {
                border: 1.5px solid #355a45;
                background: #fffaf1;
                color: #9f345c;
            }
            .ocean-auth:has(.auth-form-login) .auth-title {
                color: #9f345c;
                font-size: 28px;
            }
            .ocean-auth:has(.auth-form-login) .auth-card input:not([type="checkbox"]) {
                border: 2px solid #355a45;
                background: #fffdf9;
                box-shadow: var(--customer-shadow-control, -3px 3px 0) #b5cfb8;
            }
            .ocean-auth:has(.auth-form-login) .auth-submit {
                border: 2px solid #355a45;
                background: linear-gradient(180deg, #8aaf91, #79a486);
                color: #fff;
                box-shadow: var(--customer-shadow-control, -3px 3px 0) #355a45;
            }
            .ocean-auth:has(.auth-form-login) .auth-submit:active {
                transform: translate(-2px, 2px);
                box-shadow: -1px 1px 0 #355a45;
            }
            .ocean-auth:has(.auth-form-login) .auth-switch {
                border-top: 1px solid #c4d4c5;
            }
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-login) .auth-card {
                border-color: #78977f;
                background: #563642;
                box-shadow: var(--customer-shadow-card, -6px 6px 0) #081810;
            }
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-login) .auth-kicker,
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-login) .auth-card input:not([type="checkbox"]) {
                border-color: #78977f;
                background: #1b3027;
                color: #edf6ef;
            }
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-login) .auth-title {
                color: #ef86a3;
            }
        }
    </style>
</x-layouts.app>
