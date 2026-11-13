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

<style>
    /* Keep the navbar's brand slot fixed; enlarge only the visible artwork. */
    .ocean-primary-navbar,
    .ocean-primary-navbar-host {
        overflow: visible !important;
    }

    .ocean-primary-navbar .ocean-primary-navbar__brand {
        position: relative !important;
        display: block !important;
        width: clamp(190px, 17.5vw, 235px) !important;
        height: 72px !important;
        flex: 0 0 auto !important;
        overflow: visible !important;
    }

    .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
        position: absolute !important;
        top: 50% !important;
        left: -2rem !important;
        display: block !important;
        width: clamp(560px, 40vw, 760px) !important;
        line-height: 0 !important;
        transform: translateY(-50%) scale(1.25) !important;
        transform-origin: left center !important;
        z-index: 2;
    }

    .ocean-primary-navbar .ocean-primary-navbar__brand-logo img {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        height: auto !important;
        object-fit: contain;
    }

    @media (max-width: 1050px) {
        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(150px, 18vw, 190px) !important;
            height: 66px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(340px, 38vw, 420px) !important;
            transform: translateY(-50%) scale(1.2) !important;
        }
    }

    @media (max-width: 1023px) {
        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(80px, 20vw, 110px) !important;
            height: 54px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(210px, 29vw, 260px) !important;
            transform: translateY(-50%) scale(1.15) !important;
        }
    }

    @media (max-width: 460px) {
        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(72px, 23vw, 100px) !important;
            height: 50px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(180px, 44vw, 220px) !important;
            transform: translateY(-50%) scale(1.12) !important;
        }
    }
</style>

<div class="{{ $hostClass }}">
    <header class="ocean-primary-navbar" aria-label="Navigasi utama Ocean Paws">

        <details class="ocean-primary-navbar__mobile-menu">
            <summary class="ocean-primary-navbar__mobile-toggle" aria-label="Buka menu navigasi">
                <svg xmlns="http://www.w3.org/2000/svg" class="ocean-primary-navbar__hamburger" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#684f48" stroke-width="2.5" stroke-linecap="round" aria-hidden="true" focusable="false" style="display:block;width:22px;height:22px;">
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

        <a class="ocean-primary-navbar__brand" href="{{ $homeUrl }}" aria-label="Ocean Paws home" style="position:relative;display:block;width:clamp(190px,17.5vw,235px);height:72px;flex:0 0 auto;overflow:visible;">
            <span class="ocean-primary-navbar__brand-logo" style="position:absolute;top:50%;left:-2rem;display:block;width:clamp(560px,40vw,760px);line-height:0;transform:translateY(-50%) scale(1.25);transform-origin:left center;z-index:2;">
                <img src="{{ asset('assets/IMG_8321.PNG') }}" alt="Ocean Paws — Bringing your favorites closer" style="display:block;width:100%;max-width:none;height:auto;object-fit:contain;">
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
