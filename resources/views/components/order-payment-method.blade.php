@props(['order'])

@php
    $method = $order->batch?->getEffectivePaymentMethod();
    $imageUrl = $order->batch?->payment_method_image_url;
@endphp

@if(!$order->is_refunded && ($method || $imageUrl))
    <div class="order-payment-method">
        <strong>{{ $method?->name ?: 'QRIS' }}</strong>
        @if($method?->account_name)<p>{{ $method->account_name }}</p>@endif
        @if($imageUrl)<img src="{{ $imageUrl }}" alt="Kode pembayaran {{ $method?->name ?: 'QRIS' }}" loading="lazy">@endif
        @if($method?->instructions)<p>{{ $method->instructions }}</p>@endif
    </div>
@endif
