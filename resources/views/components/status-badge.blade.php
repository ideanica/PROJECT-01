@props(['status' => null, 'label' => null, 'color' => null])

@php
    $labelColor = is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) ? $color : '#f4f4f5';
@endphp

@if ($status)
    <span {{ $attributes->merge(['class' => 'status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium! ring-1 ring-inset ring-black/10', 'style' => 'background-color: '.$status->color.'14; color: '.$status->color]) }}>
        {{ $status->name }}
    </span>
@elseif ($label)
    <span {{ $attributes->merge(['class' => 'status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium! ring-1 ring-inset ring-black/10', 'style' => '--status-color: '.$labelColor.'; background-color: '.$labelColor]) }}>
        {{ $label }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'status-badge inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium! text-zinc-600 ring-1 ring-inset ring-zinc-200']) }}>
        Belum ada status
    </span>
@endif
