@props(['title', 'description', 'member' => null, 'user', 'photoPreview' => false])

<header class="profile-top profile-form-heading">
    <div class="profile-person">
        <div class="profile-photo">
            @if($photoPreview)
                <img src="{{ $member?->avatar_url ?: '' }}" alt="Foto profil {{ $member?->display_name ?: $user->name }}" data-photo-preview @if(!$member?->avatar_url) hidden @endif>
                <span data-photo-placeholder @if($member?->avatar_url) hidden @endif>{{ mb_strtoupper(mb_substr($member?->display_name ?: $user->name, 0, 1)) }}</span>
            @elseif($member?->avatar_url)
                <img src="{{ $member->avatar_url }}" alt="Foto profil {{ $member->display_name }}">
            @else
                <span>{{ mb_strtoupper(mb_substr($member?->display_name ?: $user->name, 0, 1)) }}</span>
            @endif
        </div>
        <div class="profile-form-heading-copy">
            <h1>{{ $title }}</h1>
            <div class="profile-member-labels">
                <span class="member-pill">{{ $member ? 'MEMBER' : 'ADMIN' }}</span>
                @if($member)<span class="member-pill profile-group-pill">{{ $member->customerGroup?->name ?? 'Belum ada group' }}</span>@endif
            </div>
            <p>{{ $description }}</p>
        </div>
    </div>
    @if($slot->isNotEmpty())<div class="profile-heading-actions">{{ $slot }}</div>@endif
</header>
