@props([
    'active' => 'home',
    'homeHref' => null,
    'mode' => 'page',
])

@php
    $homeUrl = $homeHref ?: route('home');
    $profileUrl = auth()->check() ? route('profile.show') : route('login');
    $hostClass = 'ocean-primary-navbar-host'.($mode === 'landing' ? ' is-landing' : '');
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
                    <x-public-icon name="home" variant="rounded-bold" :size="18" />
                    <span>Home</span>
                </a>
                <a class="ocean-primary-navbar__mobile-link {{ $active === 'tracking' ? 'is-active' : '' }}" href="{{ route('tracking.index') }}" @if($active === 'tracking') aria-current="page" @endif>
                    <x-public-icon name="truck" variant="rounded-bold" :size="18" />
                    <span>Tracking</span>
                </a>
                <a class="ocean-primary-navbar__mobile-link {{ $active === 'profile' ? 'is-active' : '' }}" href="{{ $profileUrl }}" @if($active === 'profile') aria-current="page" @endif>
                    <x-public-icon name="user" variant="rounded-bold" :size="18" />
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
                <x-public-icon name="home" variant="rounded-bold" class="ocean-primary-navbar__icon" :size="28" />
                <span>Home</span>
            </a>

            <a class="ocean-primary-navbar__link {{ $active === 'tracking' ? 'is-active' : '' }}" href="{{ route('tracking.index') }}" @if($active === 'tracking') aria-current="page" @endif>
                <x-public-icon name="truck" variant="rounded-bold" class="ocean-primary-navbar__icon" :size="28" />
                <span>Tracking</span>
            </a>

            <a class="ocean-primary-navbar__link {{ $active === 'profile' ? 'is-active' : '' }}" href="{{ $profileUrl }}" @if($active === 'profile') aria-current="page" @endif>
                <x-public-icon name="user" variant="rounded-bold" class="ocean-primary-navbar__icon" :size="28" />
                <span>Profile</span>
            </a>
        </nav>
    </header>
</div>
