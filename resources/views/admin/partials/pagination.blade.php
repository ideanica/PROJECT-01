{{-- Pagination ringkas admin: halaman sebelumnya + halaman aktif, lalu "…", lalu halaman terakhir. --}}
@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $pageLinkClass = 'inline-flex h-7 min-w-7 items-center justify-center rounded-[14px] border border-zinc-200 bg-white px-1.5 text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900';
        $arrowClass = 'inline-flex h-7 w-7 items-center justify-center rounded-[14px] border border-zinc-200 bg-white text-zinc-500 transition hover:bg-zinc-50 hover:text-zinc-800';
        $arrowDisabledClass = 'inline-flex h-7 w-7 items-center justify-center rounded-[14px] border border-zinc-200 text-zinc-300';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-end gap-1">
        @if ($paginator->onFirstPage())
            <span class="{{ $arrowDisabledClass }}" aria-disabled="true" aria-label="Halaman sebelumnya">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 5-5 5 5 5"/></svg>
            </span>
        @else
            <a class="{{ $arrowClass }}" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 5-5 5 5 5"/></svg>
            </a>
        @endif

        @if ($currentPage > 1)
            <a class="{{ $pageLinkClass }}" href="{{ $paginator->url($currentPage - 1) }}" aria-label="Halaman {{ $currentPage - 1 }}">{{ $currentPage - 1 }}</a>
        @endif

        <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-[14px] border border-zinc-900 bg-zinc-900 px-1.5 font-semibold text-white" aria-current="page" aria-label="Halaman {{ $currentPage }}, halaman aktif">{{ $currentPage }}</span>

        @if ($currentPage < $lastPage - 1)
            <span class="inline-flex h-7 w-7 items-center justify-center text-zinc-400" aria-hidden="true">…</span>
        @endif

        @if ($currentPage < $lastPage)
            <a class="{{ $pageLinkClass }}" href="{{ $paginator->url($lastPage) }}" aria-label="Halaman terakhir, {{ $lastPage }}">{{ $lastPage }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a class="{{ $arrowClass }}" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m8 5 5 5-5 5"/></svg>
            </a>
        @else
            <span class="{{ $arrowDisabledClass }}" aria-disabled="true" aria-label="Halaman berikutnya">
                <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m8 5 5 5-5 5"/></svg>
            </span>
        @endif
    </nav>
@endif
