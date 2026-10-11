@php
    $member = auth()->user()?->member;
    $homeActive = request()->routeIs(['home', 'tracking.*']);
    $ordersActive = request()->routeIs(['orders.*', 'billing.*', 'services.*']);
    $profileActive = request()->routeIs('profile.*');
    $isPortalDetail = request()->routeIs('billing.*') || (request()->routeIs('orders.*') && ! request()->routeIs('orders.index'));
@endphp

<style>
    /* Customer dark theme bridge.
       Several portal components predate the theme switch and still carry literal light colors.
       Keep the light palette untouched and remap the whole customer surface only when dark mode is active. */
    html[data-customer-theme="dark"] body.customer-theme {
        color-scheme: dark;
        background: #12251e;
        color: #eef6f0;
    }

    html[data-customer-theme="dark"] .customer-public-shell {
        background: #12251e;
        color: #eef6f0;
    }

    html[data-customer-theme="dark"] .public-topbar {
        border-bottom-color: rgba(142, 174, 151, .24);
        background: rgba(18, 37, 30, .94);
        box-shadow: 0 8px 26px rgba(3, 13, 9, .18);
    }

    html[data-customer-theme="dark"] .public-desktop-nav {
        border-color: #496957;
        background: rgba(29, 53, 44, .96);
        box-shadow: 0 8px 24px rgba(2, 13, 9, .28);
    }

    html[data-customer-theme="dark"] .public-desktop-nav a {
        color: #c9d9ce;
    }

    html[data-customer-theme="dark"] .public-desktop-nav a:hover,
    html[data-customer-theme="dark"] .public-desktop-nav a.active {
        background: var(--pv2-pink);
        color: #fffdf7;
    }

    html[data-customer-theme="dark"] .public-account-chip,
    html[data-customer-theme="dark"] .portal-v2-account-chip {
        border-color: #78977f !important;
        background: #1b3027 !important;
        color: #edf6ef !important;
        box-shadow: var(--customer-shadow-control) #081810 !important;
    }

    html[data-customer-theme="dark"] .public-account-chip strong,
    html[data-customer-theme="dark"] .portal-v2-account-chip strong {
        color: #edf6ef !important;
    }

    html[data-customer-theme="dark"] .public-account-chip img,
    html[data-customer-theme="dark"] .public-account-chip > span,
    html[data-customer-theme="dark"] .portal-v2-account-chip img,
    html[data-customer-theme="dark"] .portal-v2-account-chip > span {
        border-color: #78977f !important;
        background: #563642 !important;
        color: #f6dce4 !important;
        box-shadow: var(--customer-shadow-pressed) #081810 !important;
    }

    html[data-customer-theme="dark"] .public-line-login {
        border-color: #78977f;
        background: #1b3027;
        color: #edf6ef;
    }

    html[data-customer-theme="dark"] .portal-v2 {
        --pv2-ink: #edf6ef;
        --pv2-outline: #78977f;
        --pv2-ink-deep: #eef6f0;
        --pv2-text: #c6dacb;
        --pv2-muted: #aec0b4;
        --pv2-paper: #1b3027;
        --pv2-cream: #12251e;
        --pv2-pink: #4d303a;
        --pv2-pink-strong: #ef86a3;
        --pv2-mint: #31483a;
        --pv2-mint-strong: #79a486;
        --pv2-yellow: #5b502c;
        --pv2-blue: #294149;
        --pv2-border: 2px solid #78977f;
        --pv2-shadow: var(--customer-shadow-card) #081810;
        --pv2-shadow-small: var(--customer-shadow-control) #081810;
        color: var(--pv2-ink-deep);
        background:
            linear-gradient(rgba(151, 181, 159, .055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(151, 181, 159, .055) 1px, transparent 1px),
            var(--pv2-cream);
        background-size: 42px 42px;
    }

    html[data-customer-theme="dark"] .portal-v2-back,
    html[data-customer-theme="dark"] .portal-v2-filter-panel,
    html[data-customer-theme="dark"] .portal-v2-hero-stat,
    html[data-customer-theme="dark"] .portal-v2-order-card,
    html[data-customer-theme="dark"] .portal-v2-empty,
    html[data-customer-theme="dark"] .portal-v2-price-box,
    html[data-customer-theme="dark"] .portal-v2-meta-grid > div {
        border-color: #78977f;
        background: #1b3027;
        color: #eef6f0;
        box-shadow: var(--pv2-shadow-small);
    }

    html[data-customer-theme="dark"] .portal-v2-filter-panel,
    html[data-customer-theme="dark"] .portal-v2-order-card,
    html[data-customer-theme="dark"] .portal-v2-empty,
    html[data-customer-theme="dark"] .portal-v2-hero-stat {
        box-shadow: var(--customer-shadow-card) #081810;
    }

    html[data-customer-theme="dark"] .portal-v2-hero {
        border-color: #78977f;
        box-shadow: var(--customer-shadow-card) #081810;
    }

    html[data-customer-theme="dark"] .portal-v2-hero-orders {
        background: var(--pv2-pink);
    }

    html[data-customer-theme="dark"] .portal-v2-hero-billing {
        background: var(--pv2-mint);
    }

    html[data-customer-theme="dark"] .portal-v2-hero-copy h1,
    html[data-customer-theme="dark"] .portal-v2-card-title h3,
    html[data-customer-theme="dark"] .portal-v2-section-copy h2,
    html[data-customer-theme="dark"] .portal-v2-empty h2,
    html[data-customer-theme="dark"] .portal-v2-meta-grid strong,
    html[data-customer-theme="dark"] .portal-v2-price-box strong {
        color: #eef6f0;
    }

    html[data-customer-theme="dark"] .portal-v2-hero-copy p,
    html[data-customer-theme="dark"] .portal-v2-card-title p,
    html[data-customer-theme="dark"] .portal-v2-meta-grid span,
    html[data-customer-theme="dark"] .portal-v2-empty p,
    html[data-customer-theme="dark"] .portal-v2-filter-head small {
        color: #b9c9bf;
    }

    html[data-customer-theme="dark"] .portal-v2-kicker,
    html[data-customer-theme="dark"] .portal-v2-section-count,
    html[data-customer-theme="dark"] .portal-v2-section-head-icon,
    html[data-customer-theme="dark"] .portal-v2-stat-icon,
    html[data-customer-theme="dark"] .portal-v2-empty-icon,
    html[data-customer-theme="dark"] .portal-v2-thumb {
        border-color: #78977f;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .portal-v2-filters a {
        border-color: #78977f;
        background: #20382e;
        color: #dce9df;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .portal-v2-filters a:hover {
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .portal-v2-filters a.active {
        background: var(--pv2-filter-accent, var(--pv2-pink));
        color: var(--pv2-accent-text, #fff);
    }

    html[data-customer-theme="dark"] .portal-v2-filter-head,
    html[data-customer-theme="dark"] .portal-v2-section-head {
        border-color: #78977f;
    }

    html[data-customer-theme="dark"] .portal-orders-v2 .portal-v2-section-head,
    html[data-customer-theme="dark"] .portal-billing-v2 .portal-v2-section-head {
        border-color: #78977f !important;
        background: linear-gradient(120deg, #1b3027 0 72%, #39442c 72%) !important;
        box-shadow: var(--customer-shadow-card) #081810 !important;
    }

    html[data-customer-theme="dark"] .portal-v2-card-action {
        border-color: #78977f;
        background: var(--pv2-pink);
        color: #fff;
        box-shadow: var(--customer-shadow-control) #081810;
    }

    html[data-customer-theme="dark"] .portal-v2-card-action.is-secondary {
        background: #263f34;
        color: #dce9df;
    }

    html[data-customer-theme="dark"] .portal-v2-state {
        border-color: #78977f;
        background: #5b502c;
        color: #f4e6b4;
    }

    html[data-customer-theme="dark"] .portal-v2-state.is-paid {
        background: #31483a;
        color: #dceadf;
    }

    html[data-customer-theme="dark"] .portal-v2-hero::before,
    html[data-customer-theme="dark"] .portal-v2-hero::after {
        border-color: #78977f;
    }

    @media (max-width: 700px) {
        html[data-customer-theme="dark"] .portal-orders-v2 .portal-v2-section-head,
        html[data-customer-theme="dark"] .portal-billing-v2 .portal-v2-section-head {
            background: #1b3027 !important;
        }
    }
</style>

@if($isPortalDetail)
    <header aria-label="Akun customer" style="position:absolute;z-index:60;top:clamp(22px,2.5vw,34px);left:0;right:0;pointer-events:none;">
        <div style="width:min(calc(100% - clamp(28px,3.2vw,44px)),1180px);margin:0 auto;display:flex;justify-content:flex-end;align-items:center;">
            @if($member)
                <a href="{{ route('profile.show') }}" class="public-account-chip portal-v2-account-chip" style="pointer-events:auto;display:inline-flex;min-height:42px;align-items:center;gap:9px;border:2px solid #355a45;border-radius:12px;padding:5px 12px 5px 6px;background:#fffdf8;color:#355a45;box-shadow:var(--customer-shadow-control) #355a45;text-decoration:none;">
                    @if($member->avatar_url)
                        <img src="{{ $member->avatar_url }}" alt="Foto profil {{ $member->display_name }}" style="width:28px;height:28px;flex:0 0 28px;border:2px solid #355a45;border-radius:9px;background:#f8d5df;object-fit:cover;box-shadow:var(--customer-shadow-pressed) #355a45;">
                    @else
                        <span style="display:grid;width:28px;height:28px;flex:0 0 28px;place-items:center;border:2px solid #355a45;border-radius:9px;background:#f8d5df;color:#355a45;font-size:10px;font-weight:900;box-shadow:var(--customer-shadow-pressed) #355a45;">{{ mb_strtoupper(mb_substr($member->display_name, 0, 1)) }}</span>
                    @endif
                    <strong style="color:#355a45;font-family:'Trebuchet MS',Arial,system-ui,sans-serif;font-size:10px;font-weight:900;line-height:1;">{{ $member->display_name }}</strong>
                </a>
            @else
                <a href="{{ route('login') }}" class="public-line-login" style="pointer-events:auto;">Login</a>
            @endif
        </div>
    </header>
@else
    <header class="public-topbar">
        <div class="public-topbar-inner">
            <nav class="public-desktop-nav" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" class="{{ $homeActive ? 'active' : '' }}">
                    <x-public-icon name="home" :size="16" /><span>Home</span>
                </a>
                @if($member)
                    <a href="{{ route('orders.index') }}" class="{{ $ordersActive ? 'active' : '' }}">
                        <x-public-icon name="bag" :size="16" /><span>Pesanan</span>
                    </a>
                @endif
                <a href="{{ route('profile.show') }}" class="{{ $profileActive ? 'active' : '' }}">
                    <x-public-icon name="user" :size="16" /><span>Profil</span>
                </a>
            </nav>

            @if($member)
                <a href="{{ route('profile.show') }}" class="public-account-chip">
                    @if($member->avatar_url)
                        <img src="{{ $member->avatar_url }}" alt="">
                    @else
                        <span>{{ mb_strtoupper(mb_substr($member->display_name, 0, 1)) }}</span>
                    @endif
                    <strong>{{ $member->display_name }}</strong>
                </a>
            @else
                <a href="{{ route('login') }}" class="public-line-login">Login</a>
            @endif
        </div>
    </header>
@endif

