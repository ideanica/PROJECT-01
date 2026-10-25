@props(['batch', 'context' => '', 'alt' => null, 'loading' => 'lazy'])

@php
    $batchLabel = ($batch->batch_name ?: '').' '.($batch->batch_number ?: '');
    $productNames = $batch->relationLoaded('products') ? $batch->products->pluck('name')->implode(' ') : '';
    $searchableLabel = \Illuminate\Support\Str::lower($batchLabel.' '.$productNames.' '.$context);
    $fallbackImage = match (true) {
        \Illuminate\Support\Str::contains($searchableLabel, ['lightstick', 'light stick', 'penlight']) => 'lightstick.jpg',
        \Illuminate\Support\Str::contains($searchableLabel, ['photocard', 'photo card']) => 'photocard.jpg',
        \Illuminate\Support\Str::contains($searchableLabel, ['plush', 'plushie', 'boneka']) => 'plush.jpg',
        default => 'oceanpaws-about-merch-collage-v1.png',
    };
    $imageUrl = $batch->catalog_image_url ?: asset('assets/catalog-stock/'.$fallbackImage);
    $imageAlt = $alt ?: 'Gambar ilustrasi katalog '.($batch->batch_name ?: $batch->batch_number);
@endphp

<img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" loading="{{ $loading }}" decoding="async">
