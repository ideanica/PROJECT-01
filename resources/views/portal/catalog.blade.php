<x-layouts.app title="Katalog — Ocean Paws">
    <div class="portal-v2">
        <div class="portal-v2-shell">
            <a class="portal-v2-back" href="{{ route('profile.show') }}"><x-public-icon name="arrow-left" :size="18" /> Kembali ke profil</a>
            <header class="portal-v2-hero"><div class="portal-v2-hero-copy"><span class="portal-v2-kicker">KATALOG OCEAN PAWS</span><h1>Temukan WISH kamu</h1><p>Informasi batch, pilihan variasi, dan harga terbaru dari admin.</p></div></header>
            <div class="portal-v2-order-grid">
                @forelse($batches as $batch)
                    <article class="portal-v2-order-card">
                        @if($batch->catalog_image_path)<img class="catalog-batch-image" src="{{ $batch->catalog_image_url }}" alt="{{ $batch->batch_name ?: $batch->batch_number }}" loading="lazy">@endif
                        <div class="portal-v2-card-title"><small>{{ $batch->batch_number }}</small><h3>{{ $batch->batch_name ?: $batch->batch_number }}</h3><p>{{ $batch->description }}</p></div>
                        <p class="portal-v2-state">{{ $batch->catalog_is_open ? 'Pemesanan terbuka' : 'Pemesanan ditutup' }}</p>
                        @if($batch->ordering_deadline)<p>Batas pemesanan: {{ $batch->ordering_deadline->format('d M Y H:i') }}</p>@endif
                        @if($batch->currentStatus)<x-status-badge :status="$batch->currentStatus" />@endif
                        <ul class="portal-order-items">
                            @forelse($batch->products as $product)
                                <li><strong>{{ $product->name }}{{ $product->variant ? ' · '.$product->variant : '' }}</strong><br>
                                    @if($product->pivot->dp_price !== null)DP Rp {{ number_format($product->pivot->dp_price, 0, ',', '.') }} · @endif
                                    Lunas Rp {{ number_format($product->pivot->full_price ?? $product->default_price ?? 0, 0, ',', '.') }}
                                </li>
                            @empty<li>Variasi produk belum tersedia.</li>@endforelse
                        </ul>
                    </article>
                @empty<div class="portal-v2-empty"><h2>Belum ada batch yang dipublikasikan.</h2><p>Batch yang ditampilkan admin akan muncul di sini.</p></div>@endforelse
            </div>
            {{ $batches->links() }}
        </div>
    </div>
</x-layouts.app>
