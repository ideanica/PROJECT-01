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
                    <select name="customer_group_id" class="auth-select" data-auth-select2>
                        <option value="">Belum punya</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('customer_group_id') === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_group_id')<small class="auth-field-error">{{ $message }}</small>@enderror
                </label>
                <p class="auth-hint">Pilih “Belum punya” jika belum tergabung dalam group. Admin akan menentukan group dan menyetujui akun sebelum kamu bisa login.</p>
                <x-text-input label="Password" name="password" type="password" password-toggle autocomplete="new-password" minlength="8" aria-describedby="password-requirements" required />
                <x-text-input label="Konfirmasi password" name="password_confirmation" type="password" password-toggle autocomplete="new-password" minlength="8" aria-describedby="password-requirements" required />
                <x-password-requirements interactive />
                <button type="submit" class="auth-submit">Daftar akun</button>
            </form>
        <p class="auth-switch">Sudah punya akun? <a class="auth-link" href="{{ route('login') }}">Login</a></p>
    </x-auth-shell>
    <style>
        .auth-form-register .select2-container { display: block; width: 100% !important; margin-top: 6px; }
        .auth-form-register .select2-container .select2-selection--single {
            min-height: 44px; height: auto; border: 2px solid #355a45; border-radius: 8px;
            background: #fffdf9; box-shadow: -3px 3px 0 #b5cfb8;
        }
        .auth-form-register .select2-container .select2-selection__rendered {
            padding: 9px 32px 9px 11px; color: #355a45; font-size: 14px !important; line-height: 22px;
        }
        .auth-form-register .select2-container .select2-selection__arrow { height: 42px; right: 8px; }
        .auth-select2-dropdown { border: 2px solid #355a45; border-radius: 8px; background: #fffdf9; color: #355a45; font-family: "Nunito Sans", sans-serif; }
        .auth-select2-dropdown .select2-search__field { min-height: 40px; border: 1.5px solid #355a45; border-radius: 6px; padding: 8px; font-size: 14px !important; }
        .auth-select2-dropdown .select2-results__option { padding: 10px 12px; }
        .auth-select2-dropdown .select2-results__option--highlighted[aria-selected] { background: #79a486; color: #fff; }
        .auth-select2-dropdown .select2-results__option[aria-selected="true"] { background: #ffe5a8; color: #355a45; }
    </style>
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
.ocean-auth .auth-form-register .select2-selection__rendered { color: var(--auth-card-ink); font-weight: 400; }
.ocean-auth .auth-form-register .auth-select,
.auth-select2-dropdown .select2-results__option { font-weight: 400; }
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
