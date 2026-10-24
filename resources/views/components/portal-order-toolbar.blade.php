@props(['enhanceSort' => false])
<form class="portal-order-toolbar" method="GET" action="{{ url()->current() }}" role="search" aria-label="Cari dan urutkan pesanan" data-clear-search-on-empty>
    @foreach(['filter', 'tab'] as $parameter)
        @if(request()->filled($parameter))<input type="hidden" name="{{ $parameter }}" value="{{ request($parameter) }}">@endif
    @endforeach
    <label class="portal-order-search"><span>Cari pesanan</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Kode, batch, atau nama produk"></label>
    <label class="portal-order-sort"><span>Urutkan</span><select name="sort" @if($enhanceSort) data-portal-select2 @endif><option value="newest" @selected(request('sort', 'newest') === 'newest')>Terbaru</option><option value="oldest" @selected(request('sort') === 'oldest')>Terlama</option><option value="amount_desc" @selected(request('sort') === 'amount_desc')>Total tertinggi</option><option value="amount_asc" @selected(request('sort') === 'amount_asc')>Total terendah</option></select></label>
    {{ $slot }}
    <button type="submit" aria-label="Terapkan pencarian" title="Cari"><x-public-icon variant="rounded-bold" name="search" :size="18" /><span class="portal-search-submit-label">Terapkan</span></button>
</form>
