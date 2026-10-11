@props([
    'active' => 'home',
    'homeHref' => null,
    'mode' => 'page',
])

@php
    $homeUrl = $homeHref ?: route('home');
    $profileUrl = auth()->check() ? route('profile.show') : route('login');
    $hostClass = 'ocean-primary-navbar-host'.($mode === 'landing' ? ' is-landing' : '');
    $iconVariant = $mode === 'landing' ? 'rounded-bold' : 'outline';
@endphp

<div class="{{ $hostClass }}">
    <header class="ocean-primary-navbar" aria-label="Navigasi utama Ocean Paws">

        <details class="ocean-primary-navbar__mobile-menu">
            <summary class="ocean-primary-navbar__mobile-toggle" aria-label="Buka menu navigasi">
                <svg xmlns="http://www.w3.org/2000/svg" class="ocean-primary-navbar__hamburger" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#355a45" stroke-width="2.5" stroke-linecap="round" aria-hidden="true" focusable="false" style="display:block;width:22px;height:22px;">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span class="sr-only">Menu</span>
            </summary>

            <nav class="ocean-primary-navbar__mobile-panel" aria-label="Navigasi utama mobile">
                <a class="ocean-primary-navbar__mobile-link {{ $active === 'home' ? 'is-active' : '' }}" href="{{ $homeUrl }}" @if($active === 'home') aria-current="page" @endif>
                    <x-public-icon name="home" :variant="$iconVariant" :size="18" />
                    <span>Home</span>
                </a>
                <a class="ocean-primary-navbar__mobile-link {{ $active === 'tracking' ? 'is-active' : '' }}" href="{{ route('tracking.index') }}" @if($active === 'tracking') aria-current="page" @endif>
                    <x-public-icon name="truck" :variant="$iconVariant" :size="18" />
                    <span>Tracking</span>
                </a>
                <a class="ocean-primary-navbar__mobile-link {{ $active === 'profile' ? 'is-active' : '' }}" href="{{ $profileUrl }}" @if($active === 'profile') aria-current="page" @endif>
                    <x-public-icon name="user" :variant="$iconVariant" :size="18" />
                    <span>Profile</span>
                </a>
            </nav>
        </details>

        <a class="ocean-primary-navbar__brand" href="{{ $homeUrl }}" aria-label="Ocean Paws home">
            <span class="ocean-primary-navbar__brand-logo">
                <img src="{{ asset('assets/oceanpaws-logo-no-sticker.png') }}" alt="Ocean Paws — Bringing your favorites closer">
            </span>
        </a>

        <nav class="ocean-primary-navbar__links" aria-label="Navigasi utama">
            <a class="ocean-primary-navbar__link {{ $active === 'home' ? 'is-active' : '' }}" href="{{ $homeUrl }}" @if($active === 'home') aria-current="page" @endif>
                @if($mode === 'landing')
                    <x-public-icon name="home" variant="rounded-bold" class="ocean-primary-navbar__icon" :size="28" />
                @else
                <svg class="ocean-primary-navbar__icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                    <path d="M4.8 14.2 16 4.8l11.2 9.4v12.1a1.9 1.9 0 0 1-1.9 1.9h-6.1v-8.4h-6.4v8.4H6.7a1.9 1.9 0 0 1-1.9-1.9V14.2Z" fill="currentColor" stroke="var(--nav-icon-stroke)" stroke-width="2.35" stroke-linejoin="round"/>
                    <path d="m2.7 15.4 12.2-10.2a1.7 1.7 0 0 1 2.2 0l12.2 10.2" fill="none" stroke="var(--nav-icon-stroke)" stroke-width="2.35" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 28.2v-7.4h4v7.4" fill="#fffdfa" stroke="var(--nav-icon-stroke)" stroke-width="2.1" stroke-linejoin="round"/>
                </svg>
                @endif
                <span>Home</span>
            </a>

            <a class="ocean-primary-navbar__link {{ $active === 'tracking' ? 'is-active' : '' }}" href="{{ route('tracking.index') }}" @if($active === 'tracking') aria-current="page" @endif>
                @if($mode === 'landing')
                    <x-public-icon name="truck" variant="rounded-bold" class="ocean-primary-navbar__icon" :size="28" />
                @else
                <svg class="ocean-primary-navbar__icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                    <rect x="6" y="4.5" width="20" height="20" rx="2.3" fill="currentColor" stroke="var(--nav-icon-stroke)" stroke-width="2.35"/>
                    <rect x="9.4" y="8" width="13.2" height="7.1" rx="1" fill="#fffdfa" stroke="var(--nav-icon-stroke)" stroke-width="1.8"/>
                    <path d="M8.2 24.5h15.6v3.2H8.2z" fill="currentColor" stroke="var(--nav-icon-stroke)" stroke-width="2.1" stroke-linejoin="round"/>
                    <circle cx="10.7" cy="27.5" r="2.2" fill="#fffdfa" stroke="var(--nav-icon-stroke)" stroke-width="2"/>
                    <circle cx="21.3" cy="27.5" r="2.2" fill="#fffdfa" stroke="var(--nav-icon-stroke)" stroke-width="2"/>
                </svg>
                @endif
                <span>Tracking</span>
            </a>

            <a class="ocean-primary-navbar__link {{ $active === 'profile' ? 'is-active' : '' }}" href="{{ $profileUrl }}" @if($active === 'profile') aria-current="page" @endif>
                @if($mode === 'landing')
                    <x-public-icon name="user" variant="rounded-bold" class="ocean-primary-navbar__icon" :size="28" />
                @else
                <svg class="ocean-primary-navbar__icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                    <circle cx="16" cy="16" r="12.2" fill="#fffdfa" stroke="var(--nav-icon-stroke)" stroke-width="2.35"/>
                    <circle cx="16" cy="11.5" r="4.2" fill="currentColor" stroke="var(--nav-icon-stroke)" stroke-width="1.8"/>
                    <path d="M8.5 25.5c.8-4.2 3.7-6.6 7.5-6.6s6.7 2.4 7.5 6.6A11.9 11.9 0 0 1 16 28.2a11.9 11.9 0 0 1-7.5-2.7Z" fill="currentColor" stroke="var(--nav-icon-stroke)" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
                @endif
                <span>Profile</span>
            </a>
        </nav>
    </header>
</div>
