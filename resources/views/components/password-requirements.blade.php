<div id="password-requirements" class="auth-password-monitor" data-password-monitor>
    <p class="auth-password-summary" data-password-summary role="status">Belum diisi: 0 dari 6 syarat terpenuhi</p>
    <progress class="auth-password-progress" data-password-progress value="0" max="6" aria-label="Jumlah syarat password yang terpenuhi"></progress>
    <ul class="auth-password-checklist">
        @foreach(['length' => 'Minimal 8 karakter', 'uppercase' => 'Huruf besar (A-Z)', 'lowercase' => 'Huruf kecil (a-z)', 'number' => 'Angka (0-9)', 'symbol' => 'Simbol (contoh: !, @, #)', 'confirmation' => 'Konfirmasi password sesuai'] as $rule => $label)
            <li data-password-rule="{{ $rule }}"><span data-password-icon aria-hidden="true">○</span><span>{{ $label }}</span><span class="sr-only" data-password-rule-status>: belum terpenuhi</span></li>
        @endforeach
    </ul>
</div>
