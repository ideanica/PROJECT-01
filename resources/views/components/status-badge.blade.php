@props(['status' => null])

@if ($status)
    <span class="status-badge inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium! ring-1 ring-inset ring-black/10" style="background-color: {{ $status->color }}14; color: {{ $status->color }}">
        {{ $status->name }}
    </span>
@else
    <span class="status-badge inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium! text-zinc-600 ring-1 ring-inset ring-zinc-200">
        Belum ada status
    </span>
@endif
