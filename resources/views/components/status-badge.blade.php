@props(['status' => null, 'label' => null, 'color' => null])

@php
    $badgeLabel = $status?->name ?? $label ?? 'Belum ada status';
    $badgeColor = $status?->color ?? $color ?? '#e4e4e7';
    $badgeColor = preg_match('/^#[0-9a-f]{6}$/i', $badgeColor) ? $badgeColor : '#e4e4e7';
@endphp

<span {{ $attributes->merge(['class' => 'status-badge', 'title' => $badgeLabel, 'style' => '--status-color: '.$badgeColor]) }}>{{ $badgeLabel }}</span>
