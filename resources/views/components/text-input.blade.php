@props(['label', 'name', 'type' => 'text', 'value' => null, 'passwordToggle' => false])

@if($passwordToggle && $type === 'password')
<div class="auth-password-field" data-password-field>
    <label class="block" for="{{ $attributes->get('id', 'auth-'.$name) }}"><span class="text-sm font-semibold text-zinc-800">{{ $label }}</span></label>
    <div class="auth-password-control">
        <input id="{{ $attributes->get('id', 'auth-'.$name) }}" name="{{ $name }}" type="password" {{ $attributes->except('id')->merge(['class' => 'mt-2 w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-zinc-400 hover:border-zinc-300 focus:border-violet-500 focus:ring-4 focus:ring-violet-100']) }}>
        <button type="button" class="auth-password-toggle" data-password-toggle aria-label="Tampilkan {{ mb_strtolower($label) }}" aria-controls="{{ $attributes->get('id', 'auth-'.$name) }}" aria-pressed="false" data-password-label="{{ mb_strtolower($label) }}" hidden>
            <i class="bi bi-eye" aria-hidden="true"></i>
        </button>
    </div>
    @error($name)<small class="mt-1 block text-sm text-red-700">{{ $message }}</small>@enderror
</div>
@else
<label class="block">
    <span class="text-sm font-semibold text-zinc-800">{{ $label }}</span>
    <input name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value) }}" @endif {{ $attributes->merge(['class' => 'mt-2 w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-zinc-400 hover:border-zinc-300 focus:border-violet-500 focus:ring-4 focus:ring-violet-100']) }}>
    @error($name)<small class="mt-1 block text-sm text-red-700">{{ $message }}</small>@enderror
</label>
@endif
