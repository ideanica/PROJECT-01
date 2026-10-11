<x-layouts.app title="Detail Profil — Ocean Paws">
    <div class="profile-page profile-detail-page">
        <div class="profile-shell profile-edit-shell profile-detail-shell">
            <a class="profile-edit-back" href="{{ route('profile.show') }}">&larr; Kembali ke profil</a>
            <section class="section-card profile-edit-card profile-details-card">
                <x-profile-page-heading :title="$member?->display_name ?: $user->name" description="Detail profil dan informasi akunmu." :member="$member" :user="$user">
                    <a class="profile-detail-edit" href="{{ route('profile.edit') }}">Edit profil <x-public-icon name="chevron-right" :size="16" /></a>
                </x-profile-page-heading>
                @if(session('status'))<p class="profile-edit-success" role="status">{{ session('status') }}</p>@endif
                @php
                    $fieldIcons = ['Nama lengkap / penerima' => 'user', 'Nama' => 'user', 'Username LINE' => 'message', 'Group akun' => 'grid', 'Email' => 'mail', 'Email login' => 'mail', 'Nomor telepon' => 'phone', 'Alamat lengkap' => 'map-pin', 'Role' => 'check-circle'];
                    $sections = $member ? [
                        'Informasi akun' => [
                            'Nama lengkap / penerima' => $member->display_name,
                            'Username LINE' => $member->username,
                            'Group akun' => $member->customerGroup?->name ?? 'Belum ada group',
                        ],
                        'Informasi pengiriman' => [
                            'Email' => $member->email,
                            'Nomor telepon' => $member->phone,
                            'Alamat lengkap' => $member->address,
                        ],
                    ] : [
                        'Informasi akun' => [
                            'Nama' => $user->name,
                            'Email login' => $user->email,
                            'Role' => 'Admin',
                        ],
                    ];
                @endphp
                <div class="profile-details-body">
                    <div class="profile-details-sections">
                        @foreach($sections as $heading => $fields)
                            <section class="profile-details-section" aria-labelledby="profile-detail-section-{{ $loop->index }}">
                                <h2 id="profile-detail-section-{{ $loop->index }}">{{ $heading }}</h2>
                                <dl class="profile-details-list">
                                    @foreach($fields as $label => $value)
                                        <div class="profile-details-row">
                                            <dt class="profile-details-icon"><x-public-icon :name="$fieldIcons[$label]" :size="24" /><span class="sr-only">{{ $label }}</span></dt>
                                            <dd class="profile-details-value"><span class="profile-details-label" aria-hidden="true">{{ $label }}</span><span @class(['profile-details-text', 'is-empty' => !$value])>{{ $value ?: 'Belum diisi' }}</span></dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </section>
                        @endforeach
                    </div>
                    @if($member)
                        <aside class="profile-details-notice"><x-public-icon name="truck" :size="20" /><div><strong>Alamat pesanan yang sedang dikirim tetap berlaku</strong><p>Perubahan alamat profil tidak otomatis mengubah tujuan pengiriman. Hubungi admin jika perlu mengganti alamat pesanan tersebut.</p></div></aside>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
