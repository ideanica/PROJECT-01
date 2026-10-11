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
    <style>
        @media (max-width: 1023px) {
            .ocean-auth:has(.auth-form-register) .customer-public-shell {
                background: transparent;
            }
            .ocean-auth:has(.auth-form-register) .customer-public-shell > main {
                width: 90.43%;
                max-width: 1180px;
                padding: 8px 0 40px;
            }
            .ocean-auth:has(.auth-form-register) .auth-shell {
                width: 100%;
                max-width: none;
                padding: 0;
            }
            .ocean-auth:has(.auth-form-register) .auth-card {
                border: 2.5px solid #355a45;
                border-radius: 17px;
                padding: clamp(20px, 5vw, 28px);
                background: #fbd7e3;
                box-shadow: var(--customer-shadow-card, -6px 6px 0) #8aaf91;
            }
            .ocean-auth:has(.auth-form-register) .auth-kicker {
                border: 1.5px solid #355a45;
                background: #fffaf1;
                color: #9f345c;
            }
            .ocean-auth:has(.auth-form-register) .auth-title {
                color: #9f345c;
                font-size: 28px;
            }
            .ocean-auth:has(.auth-form-register) .auth-card :is(input:not([type="checkbox"]), select) {
                border: 2px solid #355a45;
                background: #fffdf9;
                box-shadow: var(--customer-shadow-control, -3px 3px 0) #b5cfb8;
            }
            .ocean-auth:has(.auth-form-register) .auth-submit {
                border: 2px solid #355a45;
                background: linear-gradient(180deg, #8aaf91, #79a486);
                color: #fff;
                box-shadow: var(--customer-shadow-control, -3px 3px 0) #355a45;
            }
            .ocean-auth:has(.auth-form-register) .auth-submit:active {
                transform: translate(-2px, 2px);
                box-shadow: -1px 1px 0 #355a45;
            }
            .ocean-auth:has(.auth-form-register) .auth-switch {
                border-top: 1px solid #c4d4c5;
            }
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-register) .auth-card {
                border-color: #78977f;
                background: #563642;
                box-shadow: var(--customer-shadow-card, -6px 6px 0) #081810;
            }
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-register) .auth-kicker,
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-register) .auth-card :is(input:not([type="checkbox"]), select) {
                border-color: #78977f;
                background: #1b3027;
                color: #edf6ef;
            }
            html[data-customer-theme="dark"] .ocean-auth:has(.auth-form-register) .auth-title {
                color: #ef86a3;
            }
        }
    </style>
</x-layouts.app>
