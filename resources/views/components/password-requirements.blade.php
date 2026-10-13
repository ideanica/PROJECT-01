@props(['interactive' => false])

<div id="password-requirements" @class(['auth-password-monitor', 'auth-password-monitor-interactive' => $interactive]) data-password-monitor>
    @if($interactive)
        <div class="auth-password-heading">
            <strong>Syarat password</strong>
            <span class="auth-password-count" data-password-count aria-hidden="true">0/6</span>
        </div>
    @endif
    <p @class(['auth-password-summary', 'sr-only' => $interactive]) data-password-summary role="status">Belum diisi: 0 dari 6 syarat terpenuhi</p>
    <progress class="auth-password-progress" data-password-progress value="0" max="6" aria-label="Jumlah syarat password yang terpenuhi"></progress>
    <ul class="auth-password-checklist">
        @foreach(($interactive ? ['length' => '8+ karakter', 'uppercase' => 'Huruf besar', 'lowercase' => 'Huruf kecil', 'number' => 'Angka', 'symbol' => 'Simbol', 'confirmation' => 'Konfirmasi cocok'] : ['length' => 'Minimal 8 karakter', 'uppercase' => 'Huruf besar (A-Z)', 'lowercase' => 'Huruf kecil (a-z)', 'number' => 'Angka (0-9)', 'symbol' => 'Simbol (contoh: !, @, #)', 'confirmation' => 'Konfirmasi password sesuai']) as $rule => $label)
            <li data-password-rule="{{ $rule }}"><span data-password-icon aria-hidden="true">○</span><span>{{ $label }}</span><span class="sr-only" data-password-rule-status>: belum terpenuhi</span></li>
        @endforeach
    </ul>
    @if($interactive)
        <p class="sr-only" data-password-feedback>Mulai ketik password, lalu konfirmasi untuk memastikan keduanya sama.</p>
    @endif
</div>
