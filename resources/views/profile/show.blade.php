@php
    $isMember = (bool) ($user?->role === 'customer' && $member);
    $isAdminUser = $user?->isAdmin() === true;
    $loginUrl = route('login');
    $profileName = $member?->display_name ?: ($isAdminUser ? $user->name : 'Login');
    $profileActionUrl = $isMember ? route('orders.history') : ($isAdminUser ? route('admin.dashboard') : $loginUrl);
    $months = collect(range(5, 0))->map(fn ($offset) => now()->subMonths($offset));
    $monthlyOrders = $months->map(fn ($month) => $orders->filter(fn ($order) => $order->created_at->isSameMonth($month))->count());
    $maxMonthlyOrders = max(1, (int) $monthlyOrders->max());
    $chartPoints = $monthlyOrders->map(fn ($count, $index) => [
        'x' => $index * 120,
        'y' => 145 - ($count / $maxMonthlyOrders) * 120,
    ]);
    $chartLinePoints = $chartPoints->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ');
    $chartDescription = $months->map(fn ($month, $index) => $month->translatedFormat('M Y').': '.$monthlyOrders[$index].' pesanan')->implode(', ');
    $monthStartOffset = $calendarMonth->dayOfWeek;
    $monthItemCount = $calendarItems->sum('quantity');
    $daysTogether = $member
        ? max(0, (int) floor($member->created_at->copy()->startOfDay()->diffInDays(now()->startOfDay())))
        : 0;
@endphp

<x-layouts.app title="Profil Member — Ocean Paws">
    <style>
        .ocean-profile .profile-page {
            padding-top: 24px;
        }

        @media (max-width: 720px) {
            .ocean-profile .profile-page {
                padding-top: 20px;
            }
        }

        @media (max-width: 560px) {
            .ocean-profile .profile-top {
                min-height: 88px;
                padding: 12px 14px;
                gap: 10px;
                align-items: center;
            }

            .ocean-profile .profile-person {
                gap: 10px;
            }

            .ocean-profile .profile-person strong {
                font-size: 15px !important;
                line-height: 1.2;
            }

            .ocean-profile .profile-photo {
                width: 44px;
                height: 44px;
                flex-basis: 44px;
                padding: 2px;
            }

            .ocean-profile .profile-member-labels {
                gap: 4px 6px;
                margin-top: 5px;
            }

            .ocean-profile .profile-login-label,
            .ocean-profile .member-pill {
                font-size: 9px;
            }

            .ocean-profile .settings-btn {
                width: 38px;
                height: 38px;
                flex: 0 0 38px;
                border-radius: 9px;
            }

            .ocean-profile .settings-btn svg {
                width: 15px;
                height: 15px;
            }

            .ocean-profile .profile-orders-section h2 {
                font-size: 16px !important;
                line-height: 1.15;
            }

            .ocean-profile .order-link .order-bubble {
                width: 42px;
                height: 42px;
                border-radius: 11px;
            }

            .ocean-profile .order-link .order-bubble svg {
                width: 16px;
                height: 16px;
            }

            .ocean-profile .profile-catalog-link .icon-chip,
            .ocean-profile .activity-head .icon-chip {
                width: 38px;
                height: 38px;
                flex: 0 0 38px;
                border-radius: 10px;
            }

            .ocean-profile .profile-catalog-link .icon-chip svg,
            .ocean-profile .activity-head .icon-chip svg {
                width: 17px;
                height: 17px;
            }

            .ocean-profile .profile-catalog-link > svg {
                width: 15px;
                height: 15px;
            }

            .ocean-profile .activity-summary {
                gap: 8px;
                padding: 12px 14px;
            }

            .ocean-profile .metric-card {
                min-height: 58px;
                gap: 8px;
                padding: 8px 9px;
                align-items: center;
            }

            .ocean-profile .metric-card .tiny-icon {
                --metric-icon-card-size: 36px;
                width: var(--metric-icon-card-size);
                height: var(--metric-icon-card-size);
                flex-basis: var(--metric-icon-card-size);
            }

            .ocean-profile .metric-card .tiny-icon svg {
                width: 14px;
                height: 14px;
            }

            .ocean-profile .metric-copy {
                display: flex;
                min-width: 0;
                height: 36px;
                flex-direction: column;
                justify-content: space-between;
            }

            .ocean-profile .metric-copy strong {
                font-size: 17px;
                line-height: 1;
            }

            .ocean-profile .metric-copy span {
                font-size: 8px;
                line-height: 1.05;
            }

            .ocean-profile .cal-arrow {
                width: 34px;
                height: 34px;
                border-radius: 9px;
            }

            .ocean-profile .cal-arrow svg {
                width: 12px;
                height: 12px;
            }
        }
    </style>
    <div class="page profile-page">
        <div class="profile-shell profile-stack">
            <h1 class="sr-only">Profil Ocean Paws</h1>

            @error('line')
                <div class="profile-login-notice" role="alert">{{ $message }}</div>
            @enderror

            <div class="profile-dashboard">
            <section class="profile-main">
                <div class="profile-top">
                    <div class="profile-person">
                        @if($isMember)
                            <div class="profile-photo">
                                @if($member->avatar_url)<img src="{{ $member->avatar_url }}" alt="Foto profil {{ $member->display_name }}">@else<span>{{ mb_strtolower(mb_substr($member->display_name, 0, 1)) }}</span>@endif
                            </div>
                            <div>
                                <strong class="block text-[14px]">{{ $profileName }}</strong>
                                <div class="profile-member-labels">
                                    <span class="member-pill">MEMBER <x-public-icon name="chevron-right" :size="10" /></span>
                                    <span class="member-pill profile-group-pill">{{ $member->customerGroup?->name ?? 'Belum ada group' }}</span>
                                </div>
                            </div>
                        @elseif($isAdminUser)
                            <a href="{{ route('admin.dashboard') }}" class="profile-person">
                                <div class="profile-photo"><span>{{ mb_strtolower(mb_substr($profileName, 0, 1)) }}</span></div>
                                <div><strong class="block text-[14px]">{{ $profileName }}</strong><span class="profile-login-label">ADMIN <x-public-icon name="chevron-right" :size="10" /></span></div>
                            </a>
                        @else
                            <a href="{{ $loginUrl }}" class="profile-person">
                                <div class="profile-photo is-empty"><span aria-hidden="true"><x-public-icon name="user" :size="23" /></span></div>
                                <div><strong class="block text-[14px]" data-i18n-id="Login" data-i18n-en="Login">Login</strong><span class="profile-login-label"><span data-i18n-id="MASUK KE AKUN" data-i18n-en="LOGIN TO ACCOUNT">MASUK KE AKUN</span> <x-public-icon name="chevron-right" :size="10" /></span></div>
                            </a>
                        @endif
                    </div>

                    @if($isMember || $isAdminUser)
                        <button type="button" class="settings-btn" aria-label="Buka pengaturan" aria-expanded="false" aria-controls="profile-settings-panel" data-profile-settings-button><x-public-icon name="settings" :size="18" /></button>
                    @endif
                </div>

                @if($isMember || $isAdminUser)
                    <section id="profile-settings-panel" class="profile-settings-panel" data-profile-settings-panel hidden>
                        <header>
                            <div><span class="micro" data-i18n-id="PREFERENSI" data-i18n-en="PREFERENCES">PREFERENSI</span><strong data-i18n-id="Pengaturan akun" data-i18n-en="Account settings">Pengaturan akun</strong></div>
                            <button type="button" aria-label="Tutup pengaturan" data-profile-settings-close><x-public-icon name="close" :size="15" /></button>
                        </header>

                        <a href="{{ route('profile.detail') }}" class="profile-preference-card">
                            <span class="profile-preference-icon"><x-public-icon name="user" :size="17" /></span>
                            <span class="profile-preference-copy"><strong>Edit profil</strong><small>{{ $isAdminUser ? 'Nama, email, dan password' : 'Identitas, kontak, dan alamat' }}</small></span>
                            <x-public-icon name="chevron-right" :size="15" />
                        </a>

                        @if($isAdminUser)
                            <a href="{{ route('admin.dashboard') }}" class="profile-preference-card">
                                <span class="profile-preference-icon"><x-public-icon name="grid" :size="17" /></span>
                                <span class="profile-preference-copy"><strong>Halaman admin</strong><small>Kelola customer dan pesanan</small></span>
                                <x-public-icon name="chevron-right" :size="15" />
                            </a>
                        @endif

                        <label class="profile-preference-card">
                            <span class="profile-preference-icon"><x-public-icon name="radio" :size="17" /></span>
                            <span class="profile-preference-copy"><strong data-i18n-id="Bahasa" data-i18n-en="Language">Bahasa</strong><small data-i18n-id="Bahasa antarmuka" data-i18n-en="Interface language">Bahasa antarmuka</small></span>
                            <span class="profile-switch-choice"><b>ID</b><input type="checkbox" data-profile-language-switch aria-label="Gunakan bahasa Inggris"><span class="profile-toggle-track"><i></i></span><b>EN</b></span>
                        </label>

                        <label class="profile-preference-card">
                            <span class="profile-preference-icon"><x-public-icon name="sparkle" :size="17" /></span>
                            <span class="profile-preference-copy"><strong data-i18n-id="Tampilan" data-i18n-en="Appearance">Tampilan</strong><small data-i18n-id="Mode halaman" data-i18n-en="Page theme">Mode halaman</small></span>
                            <span class="profile-switch-choice is-theme"><b data-i18n-id="Light" data-i18n-en="Light">Light</b><input type="checkbox" data-profile-theme-switch aria-label="Gunakan mode gelap"><span class="profile-toggle-track"><i></i></span><b data-i18n-id="Dark" data-i18n-en="Dark">Dark</b></span>
                        </label>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="profile-logout-button"><span data-i18n-id="Keluar dari akun" data-i18n-en="Log out">Keluar dari akun</span></button>
                        </form>
                    </section>
                @endif

                <section class="profile-orders-section" aria-labelledby="profile-orders-title">
                    <h2 id="profile-orders-title" data-i18n-id="Pesanan Saya" data-i18n-en="My Orders">Pesanan Saya</h2>
                    <div class="orders-icons profile-order-hub">
                        <a class="order-link" href="{{ $isMember ? route('orders.history') : $profileActionUrl }}">
                            <span class="order-bubble"><x-public-icon name="bag" :size="19" />@if($summary['orders'])<b>{{ $summary['orders'] }}</b>@endif</span>
                            <small data-i18n-id="Pesananku" data-i18n-en="My Orders">Pesananku</small>
                        </a>
                        <a class="order-link" href="{{ $isMember ? route('billing.index') : $profileActionUrl }}">
                            <span class="order-bubble"><x-public-icon name="card" :size="19" />@if($stats['unpaid'])<b>{{ $stats['unpaid'] }}</b>@endif</span>
                            <small data-i18n-id="Tagihan" data-i18n-en="Billing">Tagihan</small>
                        </a>
                    </div>
                </section>
            </section>

            @if(!$user)
                <p class="profile-line-config">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold underline">Daftar terlebih dahulu</a></p>
            @endif
            <a class="section-card profile-catalog-link" href="{{ route('catalog.index') }}">
                <span class="icon-chip"><x-public-icon name="bag" :size="20" /></span>
                <strong data-i18n-id="Lihat katalog batch" data-i18n-en="View batch catalog">Lihat katalog batch</strong>
                <x-public-icon name="chevron-right" :size="18" />
            </a>

            <section class="section-card activity-card">
                <div class="activity-head"><span class="icon-chip"><x-public-icon name="sparkle" :size="20" /></span><div><strong class="block text-[12px]" data-i18n-id="Rekap aktivitas kamu" data-i18n-en="Your activity recap">Rekap aktivitas kamu</strong><span class="block text-[10px] text-[#5a7fa4]" data-i18n-id="{{ $isMember ? 'Semua aktivitas akun customer kamu.' : 'Login untuk melihat aktivitasmu.' }}" data-i18n-en="{{ $isMember ? 'All activity from your customer account.' : 'Login to view your activity.' }}">{{ $isMember ? 'Semua aktivitas akun customer kamu.' : 'Login untuk melihat aktivitasmu.' }}</span></div></div>
                <div class="activity-summary">
                    <div class="metric-card"><span class="tiny-icon"><x-public-icon name="history" :size="16" /></span><div class="metric-copy"><strong>{{ $summary['orders'] }}</strong><span data-i18n-id="Total pesanan" data-i18n-en="Total orders">Total pesanan</span></div></div>
                    <div class="metric-card"><span class="tiny-icon"><x-public-icon name="box" :size="16" /></span><div class="metric-copy"><strong>{{ $summary['items'] }}</strong><span data-i18n-id="Jajanan tercatat" data-i18n-en="Recorded items">Jajanan tercatat</span></div></div>
                    <div class="metric-card"><span class="tiny-icon"><x-public-icon name="truck" :size="16" /></span><div class="metric-copy"><strong>{{ $stats['shipping'] }}</strong><span data-i18n-id="Paket dikirim" data-i18n-en="Shipped packages">Paket dikirim</span></div></div>
                    <div class="metric-card"><span class="tiny-icon"><x-public-icon name="calendar" :size="16" /></span><div class="metric-copy"><strong>{{ $daysTogether }}</strong><span data-i18n-id="Hari bareng Ocean Paws" data-i18n-en="Days with Ocean Paws">Hari bareng Ocean Paws</span></div></div>
                </div>
                <div class="chart-area">
                    <div class="chart-head"><span class="micro !text-[9px]" data-i18n-id="6 BULAN TERAKHIR" data-i18n-en="LAST 6 MONTHS">6 BULAN TERAKHIR</span><span class="chart-profile-total" data-i18n-id="{{ $monthlyOrders->sum() }} pesanan" data-i18n-en="{{ $monthlyOrders->sum() }} orders">{{ $monthlyOrders->sum() }} pesanan</span></div>
                    <div class="profile-line-chart" role="img" aria-label="{{ $chartDescription }}">
                        <div class="chart-y"><span>{{ $maxMonthlyOrders }}</span><span>{{ rtrim(rtrim(number_format($maxMonthlyOrders / 2, 1, ',', '.'), '0'), ',') }}</span><span>0</span></div>
                        <div class="chart-line-body">
                            <div class="chart-line-plot">
                                <svg viewBox="0 0 600 160" preserveAspectRatio="none" aria-hidden="true">
                                    <defs><linearGradient id="profile-chart-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#f3adc3" stop-opacity=".42" /><stop offset="100%" stop-color="#f3adc3" stop-opacity="0" /></linearGradient></defs>
                                    <polygon points="0,160 {{ $chartLinePoints }} 600,160" fill="url(#profile-chart-fill)" />
                                    <polyline points="{{ $chartLinePoints }}" fill="none" stroke="#d44974" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                </svg>
                                @foreach($chartPoints as $index => $point)
                                    <span class="chart-line-point" style="left:{{ $index * 20 }}%;top:{{ $point['y'] / 160 * 100 }}%" title="{{ $months[$index]->translatedFormat('F Y') }}: {{ $monthlyOrders[$index] }} pesanan" aria-hidden="true"></span>
                                @endforeach
                            </div>
                            <div class="chart-month-labels">@foreach($months as $month)<span>{{ $month->translatedFormat('M') }}</span>@endforeach</div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="profile-calendar-row" id="profile-calendar">
                <section class="section-card calendar-card">
                        <div class="calendar-head">
                            <a class="cal-arrow" href="{{ route('profile.show', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}#profile-calendar" aria-label="Bulan sebelumnya"><x-public-icon name="arrow-left" :size="14" /></a>
                            <div class="calendar-title"><strong>{{ $calendarMonth->translatedFormat('F Y') }}</strong><small data-i18n-id="{{ $monthItemCount }} item jajanan" data-i18n-en="{{ $monthItemCount }} snack items">{{ $monthItemCount }} item jajanan</small></div>
                            <a class="cal-arrow" href="{{ route('profile.show', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}#profile-calendar" aria-label="Bulan berikutnya"><x-public-icon name="arrow-right" :size="14" /></a>
                        </div>
                        <div class="weekdays">@foreach([['Min','Sun'],['Sen','Mon'],['Sel','Tue'],['Rab','Wed'],['Kam','Thu'],['Jum','Fri'],['Sab','Sat']] as [$day, $englishDay])<span data-i18n-id="{{ $day }}" data-i18n-en="{{ $englishDay }}">{{ $day }}</span>@endforeach</div>
                        <div class="calendar-grid">
                            @for($blank = 0; $blank < $monthStartOffset; $blank++)<span class="calendar-day"></span>@endfor
                            @for($day = 1; $day <= $calendarMonth->daysInMonth; $day++)<span class="calendar-day{{ $calendarMonth->isSameMonth(now()) && $day === now()->day ? ' is-today' : '' }}{{ in_array($day, $calendarOrderDays, true) ? ' has-order' : '' }}">{{ $day }}</span>@endfor
                        </div>
                </section>
                <section class="section-card profile-snack-card" aria-labelledby="profile-snack-title">
                        <div class="profile-snack-heading"><div><span class="micro" data-i18n-id="CATATAN JAJAN" data-i18n-en="SNACK LIST">CATATAN JAJAN</span><h2 id="profile-snack-title" data-i18n-id="Daftar jajanan" data-i18n-en="Your snack list">Daftar jajanan</h2></div><span class="profile-snack-count">{{ $monthItemCount }} item</span></div>
                        @if($calendarItems->isEmpty())
                            <p class="profile-snack-empty" data-i18n-id="Belum ada jajanan pada bulan ini." data-i18n-en="No snacks recorded this month.">Belum ada jajanan pada bulan ini.</p>
                        @else
                            <ul class="profile-snack-list">
                                @foreach($calendarItems as $item)
                                    <li><span class="profile-snack-name">{{ $item['name'] }}</span><span class="profile-snack-date">{{ $item['date']->translatedFormat('d M') }}</span><strong class="profile-snack-quantity">×{{ $item['quantity'] }}</strong></li>
                                @endforeach
                            </ul>
                        @endif
                </section>
            </div>
            </div>

        </div>
    </div>
</x-layouts.app>
