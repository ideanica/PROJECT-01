<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'Rekap Jajanan') }}</title>
        <meta name="design-version" content="landing-logo-palette-v21">
        <script>
            try {
                document.documentElement.dataset.customerTheme = localStorage.getItem('ocean-paws-theme') || 'light';
                document.documentElement.lang = localStorage.getItem('ocean-paws-language') || 'id';
            } catch (error) {
                document.documentElement.dataset.customerTheme = 'light';
            }
        </script>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @if(request()->routeIs('tracking.*'))
            @vite('resources/css/tracking.css')
        @endif
        @if(request()->routeIs('profile.*'))
            @vite('resources/css/profile.css')
        @endif
    </head>
    @php
        $isAdmin = auth()->check() && request()->routeIs('admin.*');
        $isAuthPage = request()->routeIs(['login', 'register', 'admin.login', 'password.*']);
        $isOceanTracking = request()->routeIs('tracking.*');
        $isOceanProfile = request()->routeIs('profile.*');
        $isTrackingLanding = request()->routeIs(['home', 'tracking.*', 'profile.*', 'billing.*', 'services.*', 'catalog.*', 'testimonials.*', 'orders.*']);
        $hasPublicDock = request()->routeIs(['home', 'tracking.index', 'tracking.search', 'profile.*', 'billing.*', 'services.*', 'catalog.*', 'testimonials.*', 'orders.*']);
        $registrationRequests = $isAdmin ? \App\Models\User::where('registration_pending', true)->count() : 0;
        $adminNav = [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'],
            ['route' => 'admin.batches.index', 'match' => 'admin.batches.*', 'label' => 'Batch', 'icon' => 'layers'],
            ['route' => 'admin.members.index', 'match' => ['admin.members.*', 'admin.customer-groups.*'], 'label' => 'Customer dan Group'.($registrationRequests ? ' ('.$registrationRequests.')' : ''), 'icon' => 'users'],
            ['route' => 'admin.order-statuses.index', 'match' => 'admin.order-statuses.*', 'label' => 'Status', 'icon' => 'route'],
        ];
        $isAdminFormRoute = request()->routeIs([
            'admin.batches.create', 'admin.batches.edit',
            'admin.member-orders.create', 'admin.member-orders.edit',
            'admin.order-statuses.create', 'admin.order-statuses.edit',
            'admin.members.create', 'admin.members.edit',
            'admin.customer-groups.create', 'admin.customer-groups.edit',
        ]);
        $adminBackItem = $isAdminFormRoute
            ? null
            : collect($adminNav)->first(fn ($item) => request()->routeIs($item['match']) && ! request()->routeIs($item['route']));
        $loginAt = session('login_at');
        if ($isAdmin && ! $loginAt) {
            $loginAt = now()->timestamp;
            session()->put('login_at', $loginAt);
        }
    @endphp
    <body class="{{ $isAdmin ? 'bg-[#fbf8f2]' : 'customer-theme' }}{{ $isOceanTracking ? ' ocean-tracking' : '' }}{{ $isOceanProfile ? ' ocean-profile' : '' }}{{ $isAuthPage ? ' ocean-auth' : '' }}{{ request()->routeIs(['orders.history', 'orders.unpaid', 'orders.shipping', 'orders.refunds']) && auth()->user()?->member ? ' has-order-workspace' : '' }} text-zinc-950 antialiased">
        @if ($isAdmin)
            <div class="admin-shell min-h-screen">
                <aside class="admin-sidebar border-b border-zinc-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:z-50 lg:flex lg:w-72 lg:flex-col lg:border-b-0 lg:border-r">
                    <div class="admin-brand-row flex h-20 items-center justify-between px-5 lg:h-24 lg:px-7">
                        <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3 font-bold tracking-tight">
                            <span class="grid h-12 w-12 shrink-0 place-items-center transition-transform group-hover:-rotate-3 group-hover:scale-105">
                                <img src="{{ asset('img/Picsart_26-08-23_02-05-04-834.png') }}" alt="Logo Ocean Paws" class="h-12 w-12 object-contain drop-shadow-sm">
                            </span>
                            <span><span class="block text-base">OceanPaws</span><span class="block text-[10px] font-bold uppercase tracking-[0.2em] text-violet-600">Admin Studio</span></span>
                        </a>
                        <a href="/" class="rounded-full border border-zinc-200 p-2 text-zinc-500 lg:hidden" aria-label="Lihat website">
                            <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                        </a>
                    </div>

                    <nav class="admin-navigation flex gap-1 overflow-x-auto px-4 pb-4 text-sm lg:block lg:flex-1 lg:space-y-1 lg:overflow-y-auto lg:px-5 lg:pb-6" aria-label="Navigasi admin">
                        <p class="mb-3 hidden px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-zinc-400 lg:block">Workspace</p>
                        @foreach ($adminNav as $item)
                            <a href="{{ route($item['route']) }}" @if (request()->routeIs($item['match'])) aria-current="page" @endif class="group flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 font-semibold transition lg:w-full">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg transition">
                                    @switch($item['icon'])
                                        @case('dashboard')<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>@break
                                        @case('users')<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>@break
                                        @case('layers')<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/></svg>@break
                                        @case('bag')<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>@break
                                        @case('box')<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m21 8-9 5-9-5 9-5 9 5Z"/><path d="m3 8 9 5v9l-9-5V8Zm18 0-9 5v9l9-5V8Z"/></svg>@break
                                        @case('route')<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="6" cy="18" r="3"/><circle cx="18" cy="6" r="3"/><path d="M6 15V9a3 3 0 0 1 3-3h6"/></svg>@break
                                        @default<svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                    @endswitch
                                </span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>

                </aside>

                <div class="admin-workspace lg:pl-72">
                    <header class="admin-topbar sticky top-0 z-40 flex h-16 items-center justify-between border-b border-zinc-200/80 bg-white/90 px-4 backdrop-blur-xl sm:px-6 lg:h-20 xl:px-10">
                        <div>
                            @if($adminBackItem)
                                <a href="{{ route($adminBackItem['route']) }}" class="admin-navbar-back">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/><path d="M9 12h11"/></svg>
                                    <span>Kembali ke {{ $adminBackItem['label'] }}</span>
                                </a>
                            @endif
                        </div>
                        <div class="flex items-center gap-5">
                            <div class="text-right leading-none" aria-label="Waktu sekarang">
                                <div class="text-sm font-bold tracking-tight text-zinc-800" data-realtime-clock>--:--:--</div>
                                <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-400" data-realtime-date>Memuat tanggal</div>
                            </div>
                            <div class="relative" data-user-menu>
                                <button type="button" class="grid h-8 w-8 place-items-center rounded-full border transition" aria-label="Buka menu pengguna" aria-haspopup="true" aria-expanded="false" data-user-menu-button>
                                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                </button>
                                <div class="admin-user-menu absolute right-0 top-full mt-2 w-64 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl shadow-zinc-900/10" data-user-menu-panel hidden>
                                    <div class="border-b border-zinc-100 px-4 py-3">
                                        <div class="text-sm font-bold text-zinc-900">{{ auth()->user()->name }}</div>
                                        <div class="mt-1 truncate text-xs text-zinc-400">{{ auth()->user()->email }}</div>
                                    </div>
                                    <div class="px-4 py-3">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-400">Durasi login</div>
                                        <div class="mt-1.5 flex items-center gap-2 text-sm font-semibold text-zinc-700">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span data-login-duration data-login-at="{{ $loginAt * 1000 }}">0 menit</span>
                                        </div>
                                    </div>
                                    <form method="POST" action="/logout" class="border-t border-zinc-100 p-2">
                                        @csrf
                                        <button class="flex w-full items-center justify-center gap-2 px-3 py-2.5 text-xs font-bold transition" type="submit">
                                            <svg viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3H3v14h5M13 6l4 4-4 4m4-4H7"/></svg>
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </header>

                    <main class="admin-page mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8 xl:px-10">
                        @if (session('status'))
                            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">{{ $errors->first() }}</div>
                        @endif
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @else
            <div class="min-h-screen customer-public-shell">
                @if($isAuthPage)
                    <x-ocean-primary-navbar active="profile" />
                @elseif($isOceanTracking)
                    @include('tracking.partials.site-header')
                @elseif($isOceanProfile)
                    @include('profile.partials.site-header')
                @elseif($isAuthPage)
                    <x-ocean-primary-navbar active="profile" />
                @elseif($hasPublicDock)
                    <x-public-navbar />
                @endif
                @unless($isTrackingLanding || $isAuthPage)
                <header class="sticky top-0 z-50 border-b border-zinc-200/70 bg-white/80 backdrop-blur-xl">
                    <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <a href="/" class="group flex items-center gap-2.5 font-bold tracking-tight text-[#123c5a]">
                            @unless($isAuthPage)
                                <img src="{{ asset('img/Picsart_26-08-23_02-05-04-834.png') }}" alt="Logo Ocean Paws" class="h-11 w-11 object-contain drop-shadow-sm transition-transform group-hover:-rotate-3 group-hover:scale-105">
                            @endunless
                            <span>Ocean Paws</span>
                        </a>
                        <nav class="flex items-center gap-1 text-sm font-medium">
                            @auth
                                <a class="rounded-full bg-zinc-950 px-4 py-2 text-white" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('profile.show') }}">{{ auth()->user()->isAdmin() ? 'Dashboard' : 'Profile' }}</a>
                            @else
                                <a class="rounded-full border border-zinc-200 bg-white px-4 py-2 shadow-sm" href="{{ route('login') }}">Login</a>
                            @endauth
                        </nav>
                    </div>
                </header>
                @endunless
                <main class="{{ $isTrackingLanding ? 'public-landing-main' : 'mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8' }}">
                    @if (!$isTrackingLanding && session('status'))
                        <div class="mx-auto mb-5 max-w-7xl rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
                    @endif
                    @if (!$isTrackingLanding && $errors->any())
                        <div class="mx-auto mb-5 max-w-7xl rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first() }}</div>
                    @endif
                    {{ $slot }}
                </main>
                @if($isOceanTracking)
                    @include('tracking.partials.site-footer')
                @endif
            </div>
        @endif
    </body>
</html>
