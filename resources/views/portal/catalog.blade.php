<x-layouts.app title="Katalog Batch — Ocean Paws">
    <main class="portal-v2 portal-catalog-page">
        <div class="portal-v2-shell">
            @php($catalogMember = auth()->user()?->member)
            <div class="catalog-page-topbar">
                <a class="portal-v2-back" href="{{ route('profile.show') }}"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali ke profil</span></a>
                @if($catalogMember)
                    <a class="catalog-page-avatar" href="{{ route('profile.show') }}" aria-label="Profil {{ $catalogMember->display_name }}">
                        @if($catalogMember->avatar_url)<img src="{{ $catalogMember->avatar_url }}" alt="Foto profil {{ $catalogMember->display_name }}">@else<span aria-hidden="true">{{ mb_strtoupper(mb_substr($catalogMember->display_name, 0, 1)) }}</span>@endif
                    </a>
                @endif
            </div>

            <header class="portal-v2-hero portal-v2-hero-orders portal-catalog-hero">
                <div class="portal-v2-hero-copy">
                    <span class="portal-v2-kicker">KATALOG OCEAN PAWS</span>
                    <h1>Temukan wish kamu</h1>
                    <p>Bandingkan batch yang tersedia dan buka detail harga serta variasi produk sebelum memilih.</p>
                </div>
                <div class="portal-catalog-hero-stat"><x-public-icon variant="rounded-bold" name="box" :size="25" /><span>{{ $batches->total() }} batch tersedia</span></div>
            </header>

            <form class="portal-order-toolbar catalog-filter-card" action="{{ route('catalog.index') }}" method="GET" role="search" aria-label="Cari dan filter katalog batch">
                <label class="portal-order-search catalog-search-field">
                    <span>Cari katalog</span>
                    <span class="catalog-search-input"><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nama batch, nomor, atau produk"></span>
                </label>
                <label class="portal-order-sort catalog-status-field"><span>Status pemesanan</span><select name="status" data-portal-select2><option value="all" @selected($status === 'all')>Semua batch</option><option value="open" @selected($status === 'open')>Pemesanan terbuka</option><option value="closed" @selected($status === 'closed')>Pemesanan ditutup</option></select></label>
                <button type="submit" aria-label="Terapkan pencarian" title="Cari"><x-public-icon variant="rounded-bold" name="search" :size="18" /><span class="portal-search-submit-label">Terapkan</span></button>
                @if(request()->filled('q') || $status !== 'all')<a class="catalog-filter-reset" href="{{ route('catalog.index') }}">Reset</a>@endif
            </form>

            <section class="catalog-batch-grid" aria-label="Daftar batch katalog">
                @forelse($batches as $batch)
                    <article class="catalog-batch-card">
                        <a class="catalog-batch-card-image" href="{{ route('catalog.show', $batch) }}" aria-label="Lihat detail {{ $batch->batch_name ?: $batch->batch_number }}">
                            @if($batch->catalog_image_path)<img src="{{ $batch->catalog_image_url }}" alt="{{ $batch->batch_name ?: $batch->batch_number }}" loading="lazy">@else<span><x-public-icon variant="rounded-bold" name="box" :size="32" /></span>@endif
                        </a>
                        <div class="catalog-batch-card-body">
                            <div class="catalog-batch-card-status"><span>{{ $batch->batch_number }}</span></div>
                            <h2>{{ $batch->batch_name ?: $batch->batch_number }}</h2>
                            @if($batch->description)<p class="catalog-batch-card-description">{{ $batch->description }}</p>@else<p class="catalog-batch-card-description is-muted">Informasi dan pilihan produk batch ini tersedia di halaman detail.</p>@endif
                            <div class="catalog-batch-card-meta">
                                <span><x-public-icon variant="rounded-bold" name="box" :size="15" />{{ $batch->products->count() }} variasi tersedia</span>
                                @if($batch->ordering_deadline)<span><x-public-icon variant="rounded-bold" name="calendar" :size="15" />{{ $batch->catalog_is_open ? 'Tutup' : 'Ditutup' }} {{ $batch->ordering_deadline->translatedFormat('d M Y, H:i') }}</span>@else<span><x-public-icon variant="rounded-bold" name="calendar" :size="15" />Jadwal menyusul</span>@endif
                            </div>
                            <a class="catalog-batch-detail-button" href="{{ route('catalog.show', $batch) }}"><span>Lihat detail batch</span><x-public-icon variant="rounded-bold" name="arrow-right" :size="17" /></a>
                        </div>
                    </article>
                @empty
                    <div class="catalog-batch-empty"><span class="catalog-empty-icon"><x-public-icon variant="rounded-bold" name="search" :size="25" /></span><h2>{{ request()->filled('q') || $status !== 'all' ? 'Batch tidak ditemukan' : 'Belum ada batch tersedia' }}</h2><p>{{ request()->filled('q') || $status !== 'all' ? 'Ubah kata kunci atau status filter untuk melihat batch lain.' : 'Batch yang dipublikasikan admin akan tampil di sini.' }}</p>@if(request()->filled('q') || $status !== 'all')<a href="{{ route('catalog.index') }}">Tampilkan semua batch</a>@endif</div>
                @endforelse
            </section>
            @if($batches->hasPages())<nav class="catalog-pagination" aria-label="Navigasi halaman katalog">{{ $batches->links() }}</nav>@endif
        </div>
    </main>
</x-layouts.app>
