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
        left: 0 !important;
        display: block !important;
        width: clamp(560px, 40vw, 760px) !important;
        line-height: 0 !important;
        transform: translate(-2rem, -50%) scale(1.25) !important;
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
            transform: translate(-2rem, -50%) scale(1.2) !important;
        }
    }

    @media (max-width: 1023px) {
        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(80px, 20vw, 110px) !important;
            height: 54px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(210px, 29vw, 260px) !important;
            transform: translate(-2rem, -50%) scale(1.15) !important;
        }
    }

    @media (max-width: 460px) {
        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(72px, 23vw, 100px) !important;
            height: 50px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(180px, 44vw, 220px) !important;
            transform: translate(-2rem, -50%) scale(1.12) !important;
        }
    }

    /* Desktop: place the brand as the first flex item and remove only
       the navbar's left inner padding so the logo starts beside the border. */
    @media (min-width: 1024px) {
        .ocean-primary-navbar {
            padding-left: 34px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand {
            position: relative !important;
            top: auto !important;
            left: auto !important;
            display: block !important;
            width: clamp(190px, 17.5vw, 235px) !important;
            height: 72px !important;
            flex: 0 0 auto !important;
            margin: 0 !important;
            transform: none !important;
            z-index: 3;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            top: 50% !important;
            left: 0 !important;
            /* The source PNG has wide transparent side margins.
               Shift its canvas so the actual artwork aligns with the left slot. */
            transform: translate(calc(-73% + 1.5rem), calc(-50% + 3px)) scale(1.5) !important;
            transform-origin: left center !important;
        }
    }

    /* Tablet navbar: keep the logo and menu balanced without desktop overflow. */
    @media (min-width: 681px) and (max-width: 1023px) {
        .ocean-primary-navbar {
            min-height: 70px !important;
            gap: 8px !important;
            padding: 0 12px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(105px, 14vw, 140px) !important;
            height: 56px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(205px, 31vw, 280px) !important;
            transform: translate(-42%, -50%) scale(1.08) !important;
        }
    }

    /* Narrow mobile navbar: retain a legible mark and a comfortable menu hit area. */
    @media (max-width: 680px) {
        .ocean-primary-navbar {
            min-height: 66px !important;
            gap: 5px !important;
            padding: 0 10px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand {
            width: clamp(94px, 29vw, 118px) !important;
            height: 50px !important;
        }

        .ocean-primary-navbar .ocean-primary-navbar__brand-logo {
            width: clamp(205px, 58vw, 235px) !important;
            /* Scale the artwork up, then keep its visible center inside the brand slot. */
            transform: translate(calc(-30% - 2rem), -50%) scale(1.42) !important;
            transform-origin: left center !important;
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

        <a class="ocean-primary-navbar__brand" href="{{ $homeUrl }}" aria-label="Ocean Paws home">
            <span class="ocean-primary-navbar__brand-logo">
                <img src="{{ asset('assets/IMG_8321.PNG') }}" alt="Ocean Paws — Bringing your favorites closer">
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
