@props(['order'])

@php
    $method = $order->batch?->getEffectivePaymentMethod();
    $imageUrl = $order->batch?->payment_method_image_url;
@endphp

@if(!$order->is_refunded && ($method || $imageUrl))
    <details class="order-payment-method">
        <summary><span class="order-payment-method-icon"><x-public-icon variant="rounded-bold" name="wallet" :size="20" /></span><span>Lihat metode pembayaran</span><x-public-icon name="chevron-right" :size="18" /></summary>
        <div class="order-payment-method-content">
            <header><span>METODE PEMBAYARAN</span><strong>{{ $method?->name ?: 'QRIS' }}</strong></header>
            @if($method?->account_name)<p class="order-payment-account"><span>Atas nama</span><strong>{{ $method->account_name }}</strong></p>@endif
            @if($imageUrl)<div class="order-payment-qr"><img src="{{ $imageUrl }}" alt="Kode pembayaran {{ $method?->name ?: 'QRIS' }}" loading="lazy"></div>@endif
            @if($method?->instructions)<div class="order-payment-instructions"><strong>Petunjuk pembayaran</strong><p>{{ $method->instructions }}</p></div>@endif
        </div>
    </details>
@endif
