@props(['register' => false, 'admin' => false])

<section class="auth-shell {{ $register ? 'auth-shell-register' : '' }}" aria-label="Akun Ocean Paws">
    <div class="auth-card">{{ $slot }}</div>
</section>
