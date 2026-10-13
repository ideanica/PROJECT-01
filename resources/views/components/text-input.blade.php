@props(['label', 'name', 'type' => 'text', 'value' => null])

<label class="block">
    <span class="text-sm font-semibold text-zinc-800">{{ $label }}</span>
    <input name="{{ $name }}" type="{{ $type }}" @if($type !== 'password') value="{{ old($name, $value) }}" @endif {{ $attributes->merge(['class' => 'mt-2 w-full rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm shadow-sm outline-none transition placeholder:text-zinc-400 hover:border-zinc-300 focus:border-violet-500 focus:ring-4 focus:ring-violet-100']) }}>
    @error($name)<small class="mt-1 block text-sm text-red-700">{{ $message }}</small>@enderror
</label>

