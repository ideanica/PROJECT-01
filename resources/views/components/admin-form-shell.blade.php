@props([
    'title',
    'description',
    'eyebrow' => 'Form Operasional',
    'maxWidth' => 'max-w-4xl',
])

<div {{ $attributes->merge(['class' => 'admin-form-shell '.$maxWidth]) }}>
    <header class="admin-form-header">
        <div class="admin-form-eyebrow">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z"/><path d="m8 9 4 2 4-2M12 11v6"/></svg>
            {{ $eyebrow }}
        </div>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </header>

    {{ $slot }}
</div>
