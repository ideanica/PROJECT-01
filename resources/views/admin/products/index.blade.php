<x-layouts.app title="Produk">
    <div class="[&_h1]:text-black!">
        <x-page-heading title="Produk" description="Master K-pop merch untuk mempercepat input item pesanan.">
            <x-slot:action><a class="admin-primary-action" href="{{ route('admin.products.create', [], false) }}">+ Tambah produk</a></x-slot:action>
        </x-page-heading>
    </div>

    <div class="order-table-card">
        <form class="order-table-toolbar">
            <input class="min-w-0 flex-1" name="q" value="{{ request('q') }}" placeholder="Cari nama atau varian produk...">
            <button class="order-table-toolbar-button" type="submit"><svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>Cari</button>
        </form>
        <div class="order-table-scroll">
            <table class="order-table responsive-card-table">
                <thead><tr><th>Produk</th><th>Varian</th><th>Harga default</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td data-label="Produk"><div class="order-table-primary">{{ $product->name }}</div><div class="order-table-secondary">ID #{{ str_pad($product->id, 4, '0', STR_PAD_LEFT) }}</div></td>
                            <td data-label="Varian" class="text-zinc-600">{{ $product->variant ?: '-' }}</td>
                            <td data-label="Harga default" class="font-semibold text-zinc-700">{{ $product->default_price ? 'Rp '.number_format($product->default_price, 0, ',', '.') : '-' }}</td>
                            <td data-label="Status"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}"><span class="h-1.5 w-1.5 rounded-full {{ $product->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td data-card-action class="text-right"><a class="order-table-action" href="{{ route('admin.products.show', $product, false) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-12 text-center text-zinc-400">Belum ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="order-table-footer"><span>Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} produk</span><div>{{ $products->links() }}</div></div>
    </div>
</x-layouts.app>
