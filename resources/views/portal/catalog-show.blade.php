@php
    $checkoutUser = auth()->user();
    $checkoutMember = $checkoutUser?->role === 'customer' ? $checkoutUser->member : null;
    $selectedQuantities = collect(old('items', []))->keyBy(fn ($item) => $item['product_id'] ?? null);
    $canCheckout = $checkoutUser?->role === 'customer'
        && $checkoutMember?->isAvailableForOrder()
        && $batch->has_payment_method
        && ! $emsAllocationFrozen;
@endphp

<x-layouts.app :title="($batch->batch_name ?: $batch->batch_number).' — Katalog Ocean Paws'">
    <main class="portal-v2 portal-catalog-page portal-catalog-detail-page">
        <div class="portal-v2-shell">
            @php
                $catalogMember = auth()->user()?->member;
            @endphp
            <div class="catalog-page-topbar">
                <a class="portal-v2-back" href="{{ route('catalog.index', request()->only('q', 'status', 'page')) }}"><x-public-icon variant="rounded-bold" name="arrow-left" :size="18" /><span>Kembali ke katalog</span></a>
                @if($catalogMember)
                    <a class="catalog-page-avatar" href="{{ route('profile.show') }}" aria-label="Profil {{ $catalogMember->display_name }}">
                        @if($catalogMember->avatar_url)<img src="{{ $catalogMember->avatar_url }}" alt="Foto profil {{ $catalogMember->display_name }}">@else<span aria-hidden="true">{{ mb_strtoupper(mb_substr($catalogMember->display_name, 0, 1)) }}</span>@endif
                    </a>
                @endif
            </div>

            <header class="catalog-detail-hero">
                <div class="catalog-detail-image">
                    <x-catalog-photo :batch="$batch" loading="eager" />
                </div>
                <div class="catalog-detail-hero-copy">
                    <div class="catalog-batch-card-status"><span>{{ $batch->batch_number }}</span></div>
                    <h1>{{ $batch->batch_name ?: $batch->batch_number }}</h1>
                    @if($batch->description)<p>{{ $batch->description }}</p>@endif
                    <div class="catalog-detail-facts">
                        <div><span>Status katalog</span><strong>{{ $batch->catalog_is_open ? 'Terbuka' : 'Ditutup' }}</strong></div>
                        <div><span>Batas pemesanan</span><strong>{{ $batch->ordering_deadline?->translatedFormat('d F Y, H:i') ?: 'Belum ditentukan' }}</strong></div>
                    </div>
                </div>
            </header>

            <section class="catalog-detail-section" aria-labelledby="catalog-products-title">
                <header class="catalog-detail-section-heading"><div><span>PRODUK BATCH</span><h2 id="catalog-products-title">Pilihan produk dan harga</h2><p>DP mengikuti persentase {{ number_format($dpPercentage, 2, ',', '.') }}% yang diatur admin. Tagihan final dihitung dari semua item pesanan.</p></div><strong>{{ $batch->products->count() }} variasi</strong></header>
                @if($batch->products->isNotEmpty())
                    <div class="catalog-detail-product-list">
                        @foreach($batch->products as $product)
                            @php
                                $fullPrice = (float) ($product->pivot->full_price ?? $product->default_price ?? 0);
                                $dpPrice = min($fullPrice, max(0.01, round($fullPrice * $dpPercentage / 100, 2)));
                            @endphp
                            <article class="catalog-detail-product">
                                <div class="catalog-detail-product-name"><span><x-public-icon variant="rounded-bold" name="box" :size="19" /></span><div><small>{{ $product->name }}</small><h3>{{ $product->variant ?: 'Varian standar' }}</h3></div></div>
                                <dl class="catalog-detail-prices"><div><dt>DP · {{ number_format($dpPercentage, 2, ',', '.') }}%</dt><dd>Rp {{ number_format($dpPrice, fmod($dpPrice, 1) > 0 ? 2 : 0, ',', '.') }}</dd></div><div><dt>Lunas</dt><dd>Rp {{ number_format($fullPrice, fmod($fullPrice, 1) > 0 ? 2 : 0, ',', '.') }}</dd></div></dl>
                                @if($canCheckout && $batch->catalog_is_open)
                                    <input type="hidden" form="catalog-checkout" name="items[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                                    <label class="catalog-checkout-quantity"><span>Jumlah</span><input form="catalog-checkout" type="number" name="items[{{ $loop->index }}][quantity]" value="{{ data_get($selectedQuantities->get($product->id), 'quantity', 0) }}" min="0" max="50" step="1" inputmode="numeric" data-checkout-quantity data-price-cents="{{ (int) round($fullPrice * 100) }}" aria-label="Jumlah {{ $product->name }} {{ $product->variant }}"></label>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="catalog-detail-empty"><h3>Belum ada variasi yang tersedia</h3><p>Periksa kembali katalog ini nanti untuk melihat pilihan produk terbaru.</p></div>
                @endif
            </section>

            @if($batch->catalog_is_open && $batch->products->isNotEmpty())
                @if($canCheckout)
                    <form id="catalog-checkout" class="catalog-checkout-card" method="POST" action="{{ route('catalog.orders.store', $batch) }}" data-catalog-checkout data-dp-percentage="{{ $dpPercentage }}">
                        @csrf
                        @if($errors->has('items') || $errors->has('batch') || $errors->has('payment_type') || $errors->has('ems_tax_amount') || $errors->has('account'))
                            <div class="catalog-checkout-error" role="alert">{{ $errors->first('items') ?: ($errors->first('batch') ?: ($errors->first('payment_type') ?: ($errors->first('ems_tax_amount') ?: $errors->first('account')))) }}</div>
                        @endif
                        <div class="catalog-checkout-heading"><span>CHECKOUT</span><h2>Konfirmasi pesanan</h2><p>Pilih skema pembayaran. Sistem menghitung total dari harga batch dan jumlah item yang dipilih.</p></div>
                        <fieldset class="catalog-checkout-payment-type"><legend>Pilih pembayaran</legend>
                            <label><input type="radio" name="payment_type" value="dp" @checked(old('payment_type', 'dp') === 'dp') data-checkout-payment-type><span><strong>Bayar DP</strong><small><span data-checkout-dp>Rp 0</span> sekarang, sisanya dilunasi nanti</small></span></label>
                            <label><input type="radio" name="payment_type" value="full" @checked(old('payment_type') === 'full') data-checkout-payment-type><span><strong>Bayar lunas</strong><small>Bayar seluruh total pesanan</small></span></label>
                        </fieldset>
                        <dl class="catalog-checkout-totals"><div><dt>Total pesanan</dt><dd data-checkout-total>Rp 0</dd></div><div><dt>Nominal pembayaran pertama</dt><dd data-checkout-due>Rp 0</dd></div></dl>
                        <p class="catalog-checkout-error" data-checkout-error role="alert" hidden>Pilih setidaknya satu produk sebelum melanjutkan.</p>
                        <button class="catalog-checkout-submit" type="submit">Buat pesanan dan lanjutkan bayar</button>
                        <small class="catalog-checkout-note">Tagihan tersimpan pada pesanan. Pembayaran dianggap lunas setelah admin memverifikasi bukti transfer.</small>
                    </form>
                @elseif(!auth()->check())
                    <aside class="catalog-detail-note"><x-public-icon variant="rounded-bold" name="user" :size="20" /><p>Masuk dengan akun customer yang disetujui untuk membuat pesanan. <a href="{{ route('login') }}">Masuk ke akun</a></p></aside>
                @elseif(! $checkoutMember || ! $checkoutMember->isAvailableForOrder())
                    <aside class="catalog-detail-note"><x-public-icon variant="rounded-bold" name="user" :size="20" /><p>Akun member belum aktif atau belum disetujui untuk membuat pesanan. Hubungi admin bila perlu bantuan.</p></aside>
                @elseif($emsAllocationFrozen)
                    <aside class="catalog-detail-note"><x-public-icon variant="rounded-bold" name="document" :size="20" /><p>Tagihan EMS batch sudah diproses sehingga alokasi pelanggan dikunci. Pemesanan baru untuk batch ini tidak tersedia.</p></aside>
                @else
                    <aside class="catalog-detail-note"><x-public-icon variant="rounded-bold" name="document" :size="20" /><p>Metode pembayaran batch belum tersedia. Hubungi admin sebelum membuat pesanan.</p></aside>
                @endif
            @endif

            @if($batch->currentStatus || $batch->ordering_deadline)
                <aside class="catalog-detail-note"><x-public-icon variant="rounded-bold" name="document" :size="20" /><p>@if($batch->currentStatus)<strong>Status batch:</strong> {{ $batch->currentStatus->name }}.@endif @if($batch->ordering_deadline)Batas pemesanan {{ $batch->ordering_deadline->translatedFormat('d F Y, H:i') }}.@endif</p></aside>
            @endif

        </div>
    </main>
    @if($canCheckout && $batch->catalog_is_open)
        <script>
            document.querySelectorAll('[data-catalog-checkout]').forEach((form) => {
                const quantities = [...document.querySelectorAll('[data-checkout-quantity]')];
                const percentage = Number(form.dataset.dpPercentage || 0);
                const totalOutput = form.querySelector('[data-checkout-total]');
                const dpOutput = form.querySelector('[data-checkout-dp]');
                const dueOutput = form.querySelector('[data-checkout-due]');
                const error = form.querySelector('[data-checkout-error]');
                const formatMoney = (cents) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(cents / 100);
                const updateTotals = () => {
                    const totalCents = quantities.reduce((sum, input) => sum + Math.max(0, Number.parseInt(input.value || '0', 10)) * Number(input.dataset.priceCents || 0), 0);
                    const dpCents = Math.min(totalCents, Math.max(totalCents ? 1 : 0, Math.round(totalCents * percentage / 100)));
                    const isFull = form.querySelector('[data-checkout-payment-type]:checked')?.value === 'full';
                    totalOutput.textContent = formatMoney(totalCents);
                    dpOutput.textContent = formatMoney(dpCents);
                    dueOutput.textContent = formatMoney(isFull ? totalCents : dpCents);
                };
                quantities.forEach((input) => input.addEventListener('input', updateTotals));
                form.querySelectorAll('[data-checkout-payment-type]').forEach((input) => input.addEventListener('change', updateTotals));
                form.addEventListener('submit', (event) => {
                    const hasItem = quantities.some((input) => Number.parseInt(input.value || '0', 10) > 0);
                    error.hidden = hasItem;
                    if (!hasItem) event.preventDefault();
                });
                updateTotals();
            });
        </script>
    @endif
</x-layouts.app>
