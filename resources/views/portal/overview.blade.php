<x-layouts.app :title="$title.' — Ocean Paws'">
    <div class="page portal-overview-page">
        <div class="portal-overview-shell">
            <header class="portal-overview-header">
                <span>{{ $type === 'billing' ? 'TAGIHAN OCEAN PAWS' : 'LAYANAN OCEAN PAWS' }}</span>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </header>

            <section class="overview-grid portal-overview-grid {{ $type === 'billing' ? 'is-billing' : 'is-services' }}" aria-label="{{ $title }}">
                @foreach($items as $item)
                    <article class="overview-card portal-overview-card">
                        <span class="icon-chip"><x-public-icon variant="rounded-bold" :name="$item['icon']" :size="21" /></span>
                        <h2>{{ $item['title'] }}</h2>
                        <p>{{ $item['description'] }}</p>
                        <div class="overview-card-foot">
                            <span>{{ $item['note'] }}</span>
                            <a class="open" href="{{ $item['url'] }}">Buka <x-public-icon variant="rounded-bold" name="chevron-right" :size="14" /></a>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-layouts.app>
