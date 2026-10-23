@props(['name' => 'grid', 'size' => 20, 'variant' => 'outline'])

@php
    $roundedBold = $variant === 'rounded-bold';
    $roundedPaths = [
        'login' => '<rect x="12" y="3" width="9" height="18" rx="3"/><path d="M3 12h12m-4-4 4 4-4 4"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'home' => '<path d="m4.4 10.2 6.3-5.6a2 2 0 0 1 2.6 0l6.3 5.6a2 2 0 0 1 .7 1.5V18a3 3 0 0 1-3 3H6.7a3 3 0 0 1-3-3v-6.3a2 2 0 0 1 .7-1.5Z"/><path d="M9 21v-6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v6"/>',
        'user' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="9" r="3" fill="var(--portal-icon-fill-pink, #ffc4d9)"/><path d="M5.5 18a7 7 0 0 1 13 0"/>',
        'menu' => '<rect x="3" y="3" width="18" height="18" rx="5"/><path d="M7 8h10M7 12h10M7 16h6"/>',
        'arrow-forward' => '<path d="M4 10h9V6l7 6-7 6v-4H4a2 2 0 0 1 0-4Z"/>',
        'bag' => '<path d="M7 8h10a2 2 0 0 1 2 2l-.7 8a3 3 0 0 1-3 3H8.7a3 3 0 0 1-3-3L5 10a2 2 0 0 1 2-2Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18M7 15h3"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2.2"/><rect x="14" y="3" width="7" height="7" rx="2.2"/><rect x="3" y="14" width="7" height="7" rx="2.2"/><rect x="14" y="14" width="7" height="7" rx="2.2"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="15" rx="3"/><path d="M4 7V6a3 3 0 0 1 3-3h10M21 11h-5a2 2 0 0 0 0 5h5"/><circle cx="16.5" cy="13.5" r=".65" fill="currentColor" stroke="none"/>',
        'truck' => '<path d="M5 17H3V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10H9M15 9h2.6a2 2 0 0 1 1.6.8l2.4 3.2a2 2 0 0 1 .4 1.2V17h-2M15 17h1"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>',
        'box' => '<path d="m11 3.6-7 3.8a2 2 0 0 0-1 1.8v5.6a2 2 0 0 0 1 1.8l7 3.8a2 2 0 0 0 2 0l7-3.8a2 2 0 0 0 1-1.8V9.2a2 2 0 0 0-1-1.8l-7-3.8a2 2 0 0 0-2 0Z"/><path d="m3.5 8 8.5 4.7L20.5 8M12 13v7M8 5.2l8 4.5"/>',
        'document' => '<path d="M13 3H7a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V10a3 3 0 0 0-.9-2.1l-4-4A3 3 0 0 0 13 3Z"/><path d="M13 3v5a2 2 0 0 0 2 2h5M8 14h8M8 17h5"/>',
        'sparkle' => '<path d="M12 3c1.2 5.2 2.8 6.8 8 8-5.2 1.2-6.8 2.8-8 8-1.2-5.2-2.8-6.8-8-8 5.2-1.2 6.8-2.8 8-8ZM20 18v4M18 20h4"/>',
        'history' => '<circle cx="12" cy="12" r="9" stroke="none" fill="var(--portal-icon-fill-mint, #bce3cf)"/><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l3 2"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9" fill="var(--portal-icon-fill-mint, #bce3cf)"/><path d="m8 12 2.5 2.5L16 9"/>',
    ];
    $iconFills = [
        'bag' => 'pink', 'card' => 'pink', 'wallet' => 'mint',
        'box' => 'cream', 'document' => 'cream', 'sparkle' => 'cream',
        'login' => 'pink', 'search' => 'mint', 'home' => 'pink',
        'user' => 'mint', 'menu' => 'pink', 'arrow-forward' => 'cream',
    ];
    $fillFallbacks = ['pink' => '#ffc4d9', 'mint' => '#bce3cf', 'cream' => '#ffe5a8'];
    if ($roundedBold && isset($iconFills[$name])) {
        $fill = $iconFills[$name];
        $roundedPaths[$name] = preg_replace('/<(path|rect) /', '<$1 fill="var(--portal-icon-fill-'.$fill.', '.$fillFallbacks[$fill].')" ', $roundedPaths[$name], 1);
    }
    if ($roundedBold && $name === 'grid') {
        $roundedPaths[$name] = str_replace('<rect ', '<rect fill="var(--portal-icon-fill-pink, #ffc4d9)" ', $roundedPaths[$name]);
    }
@endphp

<svg {{ $attributes->merge(['class' => 'public-icon'.($roundedBold ? ' public-icon-rounded-bold' : '')]) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $roundedBold ? '2.6' : '1.8' }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @if($roundedBold && isset($roundedPaths[$name]))
        @if($name === 'truck')
            <path d="M3 17V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10Z" fill="var(--portal-icon-fill-mint, #bce3cf)" stroke="none"/>
            <path d="M15 9h2.6a2 2 0 0 1 1.6.8l2.4 3.2a2 2 0 0 1 .4 1.2V17h-7Z" fill="var(--portal-icon-fill-cream, #ffe5a8)" stroke="none"/>
        @endif
        {!! $roundedPaths[$name] !!}
    @else
    @switch($name)
        @case('login')<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M3 12h12m-4-4 4 4-4 4"/>@break
        @case('home')<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/>@break
        @case('bag')<path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>@break
        @case('heart')<path d="M12 21s-8.5-5.1-8.5-11.2A4.8 4.8 0 0 1 12 6.7a4.8 4.8 0 0 1 8.5 3.1C20.5 15.9 12 21 12 21Z" fill="currentColor" stroke="none"/>@break
        @case('card')<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/>@break
        @case('grid')<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>@break
        @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
        @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>@break
        @case('phone')<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.8 2.1Z"/>@break
        @case('map-pin')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>@break
        @case('arrow-left')<path d="m15 18-6-6 6-6"/>@break
        @case('arrow-right')<path d="m9 18 6-6-6-6"/>@break
        @case('eye')<path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/>@break
        @case('chevron-right')<path d="m9 18 6-6-6-6"/>@break
        @case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
        @case('settings')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1V21h-4v-.09a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4H3v-4h.09a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1V3h4v.09a1.7 1.7 0 0 0 1.1 1.5 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.12.36.33.7.6 1 .28.27.62.48 1 .6h.09v4H21a1.7 1.7 0 0 0-1.6.4Z"/>@break
        @case('link')<path d="M10 13a5 5 0 0 0 7.07.07l2-2a5 5 0 0 0-7.07-7.07l-1.15 1.15"/><path d="M14 11a5 5 0 0 0-7.07-.07l-2 2A5 5 0 0 0 12 20l1.15-1.15"/>@break
        @case('wallet')<path d="M20 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h15v10a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6"/><path d="M16 13h4"/>@break
        @case('history')<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>@break
        @case('truck')<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>@break
        @case('swap')<path d="M7 7h12l-3-3"/><path d="m19 7-3 3"/><path d="M17 17H5l3 3"/><path d="m5 17 3-3"/>@break
        @case('gift')<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18"/><path d="M7.5 8C5.5 8 5 6.5 5.8 5.4 7 3.8 10 5 12 8c2-3 5-4.2 6.2-2.6C19 6.5 18.5 8 16.5 8"/>@break
        @case('sparkle')<path d="m12 3 1.3 4.2L17.5 8.5l-4.2 1.3L12 14l-1.3-4.2-4.2-1.3 4.2-1.3L12 3Z"/><path d="m19 14 .7 2.3L22 17l-2.3.7L19 20l-.7-2.3L16 17l2.3-.7L19 14Z"/><path d="m5 15 .7 2.3L8 18l-2.3.7L5 21l-.7-2.3L2 18l2.3-.7L5 15Z"/>@break
        @case('box')<path d="m21 8-9 5-9-5 9-5 9 5Z"/><path d="m3 8 9 5 9-5v8l-9 5-9-5V8Z"/><path d="M12 13v8"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>@break
        @case('document')<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>@break
        @case('check-circle')<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>@break
        @case('upload')<path d="M12 16V4"/><path d="m7 9 5-5 5 5"/><path d="M5 14v5h14v-5"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('minus')<path d="M5 12h14"/>@break
        @case('close')<path d="m6 6 12 12M18 6 6 18"/>@break
        @case('message')<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>@break
        @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>@break
        @case('radio')<circle cx="6" cy="12" r="1.4"/><path d="M9 9.2a4 4 0 0 1 0 5.6M12 6.5a7.8 7.8 0 0 1 0 11"/>@break
        @case('send')<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>@break
        @default<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>
    @endswitch
    @endif
</svg>
