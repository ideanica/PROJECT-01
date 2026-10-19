@php
    $isGroupPage = request()->routeIs('admin.customer-groups.index');
    $pending = !$isGroupPage && request('registration') === 'pending';
    $tabs = [
        ['label' => 'Customer / Buyer', 'route' => 'admin.members.index', 'params' => [], 'active' => !$isGroupPage && !$pending, 'icon' => 'users'],
        ['label' => 'Permintaan registrasi', 'route' => 'admin.members.index', 'params' => ['registration' => 'pending'], 'active' => $pending, 'icon' => 'clock'],
        ['label' => 'Label Group', 'route' => 'admin.customer-groups.index', 'params' => [], 'active' => $isGroupPage, 'icon' => 'folder'],
    ];
@endphp
<nav class="status-folder-tabs [&_.status-folder-tab:hover]:bg-[#eef4ef]! [&_.status-folder-tab:hover]:text-[#275a3a]! [&_.status-folder-tab.is-active]:text-[#275a3a]! [&_.status-folder-tab.is-active::after]:bg-[#2f6b46]! [&_.status-folder-tab.is-active_small]:bg-[#eef4ef]! [&_.status-folder-tab.is-active_small]:text-[#275a3a]!" aria-label="Kelola customer dan group">
    @foreach($tabs as $tab)
        <a href="{{ route($tab['route'], $tab['params'], false) }}" class="status-folder-tab {{ $tab['active'] ? 'is-active' : '' }}" @if($tab['active']) aria-current="page" @endif @if($tab['icon'] === 'clock') aria-label="Permintaan registrasi ({{ $pendingCount }})" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true" class="fill-none! stroke-current" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                @if($tab['icon'] === 'users')
                    <circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M21 21v-2a6 6 0 0 0-4-5.65"/>
                @elseif($tab['icon'] === 'clock')
                    <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                @else
                    <path d="M3 7V5a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>
                @endif
            </svg>
            <span>{{ $tab['label'] }}</span>
            @if($tab['icon'] === 'clock')<small>{{ $pendingCount }}</small>@endif
        </a>
    @endforeach
</nav>
