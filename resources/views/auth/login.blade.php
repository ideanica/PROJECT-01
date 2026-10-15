<x-layouts.app title="Login">
    <x-auth-shell>
        <span class="auth-kicker">WELCOME BACK</span>
        <h1 class="auth-title">Login</h1>
        <p class="auth-description">Masuk ke akun Ocean Paws.</p>
        <form method="POST" action="{{ route('login') }}" class="auth-form auth-form-login">
            @csrf
            <x-text-input label="Username LINE atau email" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus />
            <x-text-input label="Password" name="password" type="password" password-toggle autocomplete="current-password" required />
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
.ocean-auth:has(:is(.auth-form-login, .auth-form-register)) {
    --auth-card-outline: #496d54;
    --auth-card-paper: #fffdfa;
    --auth-card-pink: #fbd7e3;
    --auth-card-matcha: #96c88a;
    --auth-card-ink: #355a45;
    --auth-card-muted: #617369;
    --auth-card-shadow: #eba4bc;
    --auth-control-shadow: #90ba9b;
    --auth-button-shadow: #4d8156;
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) {
    width: 100%; max-width: 480px; padding: 0; margin-inline: auto;
    font-family: "Nunito Sans", sans-serif;
}
.ocean-auth .auth-shell:has(.auth-form-register) { max-width: 560px; }
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-card {
    padding: clamp(20px, 5vw, 28px); border: 2px solid var(--auth-card-outline);
    border-radius: 18px; background: var(--auth-card-pink);
    box-shadow: var(--customer-shadow-card, -6px 6px 0) var(--auth-card-shadow);
    color: var(--auth-card-ink);
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-kicker {
    border: 1.5px solid var(--auth-card-outline); border-radius: 8px;
    background: var(--auth-card-paper); color: var(--auth-card-ink);
    box-shadow: var(--customer-shadow-control, -3px 3px 0) #dd9eb3;
    font-size: 10px; font-weight: 900;
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-title {
    color: var(--auth-card-ink); font-family: "Nunito Sans", sans-serif;
    font-size: clamp(26px, 6vw, 32px); font-weight: 900; line-height: 1.15;
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) :is(.auth-description, .auth-hint) { color: var(--auth-card-muted); }
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) :is(input:not([type="checkbox"]), select, .select2-selection--single) {
    border: 2px solid var(--auth-card-outline); border-radius: 12px;
    background: var(--auth-card-paper); color: var(--auth-card-ink);
    box-shadow: var(--customer-shadow-control, -3px 3px 0) var(--auth-control-shadow);
    font-family: "Nunito Sans", sans-serif; font-size: 14px !important;
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-submit {
    min-height: 50px; border: 2px solid var(--auth-card-outline); border-radius: 12px;
    background: var(--auth-card-matcha); color: var(--auth-card-ink);
    box-shadow: var(--customer-shadow-control, -3px 3px 0) var(--auth-button-shadow);
    font-family: "Nunito Sans", sans-serif; font-size: 16px; font-weight: 900;
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-submit:is(:hover, :active) {
    transform: translate(-2px, 2px); box-shadow: var(--customer-shadow-pressed, -1px 1px 0) var(--auth-button-shadow);
}
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-submit:disabled { transform: none; box-shadow: none; }
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-switch { border-top: 1.5px solid var(--auth-card-outline); }
.ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) .auth-password-monitor {
    border: 1.5px solid var(--auth-card-outline); background: var(--auth-card-paper); border-radius: 10px;
}
.ocean-auth .auth-form-register .select2-selection__rendered { color: var(--auth-card-ink); }
@media (max-width: 1023px) {
    .ocean-auth:has(:is(.auth-form-login, .auth-form-register)) .customer-public-shell { background: transparent; }
    .ocean-auth:has(:is(.auth-form-login, .auth-form-register)) .customer-public-shell > main {
        width: 90.43%; max-width: 1180px; margin-inline: auto; padding: 8px 0 40px;
    }
    .ocean-auth .auth-shell:has(:is(.auth-form-login, .auth-form-register)) { max-width: 560px; }
}
html[data-customer-theme="dark"] .ocean-auth:has(:is(.auth-form-login, .auth-form-register)) {
    --auth-card-outline: #9dc5a4; --auth-card-paper: #284338;
    --auth-card-pink: #6a4050; --auth-card-matcha: #54784b;
    --auth-card-ink: #e6f2e6; --auth-card-muted: #c5d7ca;
    --auth-card-shadow: #081810; --auth-control-shadow: #081810; --auth-button-shadow: #081810;
}
</style>
</x-layouts.app>
