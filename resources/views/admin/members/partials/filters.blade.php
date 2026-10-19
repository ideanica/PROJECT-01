@php($isGroupPage = request()->routeIs('admin.customer-groups.index'))
<form class="border-b border-zinc-200 px-4 py-3 text-[12px] sm:px-5" data-auto-filter>
    @if(request('registration') === 'pending' && !$isGroupPage)<input type="hidden" name="registration" value="pending">@endif
    <div class="flex flex-wrap items-center gap-1.5 [&_.select2-container]:w-full! lg:[&_.select2-container]:w-auto!">
        <label class="flex h-9 w-full items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition focus-within:border-[#2f6b46]/45 focus-within:shadow-[0_0_0_4px_rgba(47,107,70,0.12)] lg:w-[18rem]">
            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5 shrink-0 text-zinc-700" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
            <input class="h-full w-full border-0! bg-transparent! p-0! text-zinc-700 shadow-none! outline-none! focus:shadow-none!" name="q" value="{{ request('q') }}" aria-label="{{ $isGroupPage ? 'Cari group' : 'Cari customer' }}" placeholder="{{ $isGroupPage ? 'Cari nama atau keterangan group...' : 'Cari nama, username LINE, telepon, atau kode...' }}">
        </label>

        @unless($isGroupPage)
        <select class="h-8 w-full cursor-pointer rounded-xl border-zinc-200! bg-white! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]! lg:w-auto" name="customer_group_id" aria-label="Filter group">
            <option value="">Semua group</option>
            @foreach($groups as $group)<option value="{{ $group->id }}" @selected((string) request('customer_group_id') === (string) $group->id)>{{ $group->name }} · {{ $group->members_count }} anggota</option>@endforeach
        </select>
        @endunless
        <select class="h-8 w-full cursor-pointer rounded-xl border-zinc-200! bg-white! py-0! text-zinc-600! shadow-[0_1px_2px_rgba(24,24,27,0.05)]! outline-none! focus:border-[#2f6b46]! focus:shadow-[0_0_0_4px_rgba(47,107,70,0.12)]! lg:w-auto" name="status" aria-label="Filter status">
            <option value="">Semua status</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
        </select>

        <button class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white px-4 font-semibold text-[#27272a] shadow-[0_1px_2px_rgba(24,24,27,0.05)] transition hover:bg-[#f4f4f5] focus:outline-none focus:ring-4 focus:ring-zinc-900/5 lg:w-auto" type="submit">
            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="9" r="5.5"/><path d="m13 13 4 4"/></svg>
            Cari
        </button>
    </div>
</form>
