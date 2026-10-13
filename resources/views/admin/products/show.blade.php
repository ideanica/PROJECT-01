<x-layouts.app title="Detail {{ $product->name }}">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Detail Produk" description="Informasi katalog dan pemakaian produk dalam satu tampilan.">
            <x-slot:action>
                <div class="flex flex-wrap gap-2">
                    <a class="admin-form-secondary" href="{{ route('admin.products.edit', $product, false) }}">Edit Produk</a>
                </div>
            </x-slot:action>
        </x-page-heading>
    </div>


    <article class="detail-record-card">
        <header class="detail-record-hero">
            <div class="min-w-0">
                <p class="detail-record-kicker">Master Produk</p>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <h2 class="detail-record-title">{{ $product->name }}</h2>
                    <span class="detail-record-state {{ $product->is_active ? 'detail-record-state-active' : 'detail-record-state-muted' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <p class="detail-record-description">{{ $product->variant ?: 'Tanpa varian' }}</p>
            </div>
            <span class="detail-record-id">ID #{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</span>
        </header>

        <section class="detail-record-section">
            <div class="detail-record-summary sm:grid-cols-3">
                <div class="detail-record-field detail-record-field-violet"><span>Harga default</span><strong>{{ $product->default_price ? 'Rp '.number_format($product->default_price, 0, ',', '.') : 'Belum ditentukan' }}</strong></div>
                <div class="detail-record-field detail-record-field-blue"><span>Dipakai di batch</span><strong>{{ $product->batches_count }} batch</strong></div>
                <div class="detail-record-field detail-record-field-cyan"><span>Item pesanan</span><strong>{{ $product->order_items_count }} item</strong></div>
            </div>
        </section>

        <section class="detail-record-section">
            <div class="detail-record-field detail-record-field-plain">
                <span>Deskripsi produk</span>
                <p>{{ $product->description ?: 'Belum ada deskripsi produk.' }}</p>
            </div>
        </section>
    </article>
</x-layouts.app>
