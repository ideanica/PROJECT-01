<x-layouts.app title="Shipment {{ $batch->batch_number }}">
    <x-page-heading title="Shipment Groups" description="Kelola jalur pengiriman, resi, status, DETAILS, dan LAST CO secara terpisah di dalam satu Batch.">
        <x-slot:action>
            <a class="admin-form-secondary" href="{{ route('admin.batches.show', $batch, false) }}">Kembali ke batch</a>
        </x-slot:action>
    </x-page-heading>

    <div class="grid gap-6 xl:grid-cols-[1fr_430px]">
        <section class="space-y-4">
            @forelse($batch->shipmentGroups as $shipment)
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <form method="POST" action="{{ route('admin.batches.shipments.update', [$batch, $shipment], false) }}" class="grid gap-4 md:grid-cols-2">
                        @csrf @method('PUT')
                        <x-text-input label="Nama group" name="name" :value="$shipment->name" required />
                        <label class="block"><span>Metode</span><select name="method">@foreach(['ems'=>'EMS','air_cargo'=>'Air Cargo','sea_cargo'=>'Sea Cargo','handcarry'=>'Handcarry','online_handcarry'=>'Online Handcarry','other'=>'Other'] as $value=>$label)<option value="{{ $value }}" @selected($shipment->method===$value)>{{ $label }}</option>@endforeach</select></label>
                        <x-text-input label="RESI / Tracking" name="tracking_number" :value="$shipment->tracking_number" />
                        <x-text-input label="Code" name="shipment_code" :value="$shipment->shipment_code" />
                        <label class="block"><span>Status</span><select name="current_status_id"><option value="">Belum ada</option>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected($shipment->current_status_id===$status->id)>{{ $status->name }}</option>@endforeach</select></label>
                        <x-text-input label="LAST CO" name="last_co_at" type="date" :value="$shipment->last_co_at?->format('Y-m-d')" />
                        <div class="md:col-span-2"><x-text-input label="DETAILS URL" name="details_url" type="url" :value="$shipment->details_url" /></div>
                        <label class="md:col-span-2 block"><span>Catatan</span><textarea name="notes" rows="2">{{ $shipment->notes }}</textarea></label>
                        <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-3"><small>{{ $shipment->items->count() }} item terhubung</small><button class="admin-form-primary" type="submit">Simpan perubahan</button></div>
                    </form>
                    <form class="mt-3" method="POST" action="{{ route('admin.batches.shipments.destroy', [$batch, $shipment], false) }}" onsubmit="return confirm('Hapus shipment group ini?')">@csrf @method('DELETE')<button class="admin-form-danger-action" type="submit">Hapus shipment group</button></form>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white p-6 text-sm text-zinc-500">Belum ada shipment group. Tambahkan EMS, cargo, atau handcarry dari form di samping.</div>
            @endforelse
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold">Tambah shipment group</h2>
            <form class="mt-5 space-y-4" method="POST" action="{{ route('admin.batches.shipments.store', $batch, false) }}">
                @csrf
                <x-text-input label="Nama group" name="name" placeholder="Contoh: EMS Batch 2" required />
                <label class="block"><span>Metode</span><select name="method"><option value="ems">EMS</option><option value="air_cargo">Air Cargo</option><option value="sea_cargo">Sea Cargo</option><option value="handcarry">Handcarry</option><option value="online_handcarry">Online Handcarry</option><option value="other">Other</option></select></label>
                <x-text-input label="RESI / Tracking" name="tracking_number" />
                <x-text-input label="Code" name="shipment_code" />
                <label class="block"><span>Status</span><select name="current_status_id"><option value="">Belum ada</option>@foreach($statuses as $status)<option value="{{ $status->id }}">{{ $status->name }}</option>@endforeach</select></label>
                <x-text-input label="LAST CO" name="last_co_at" type="date" />
                <x-text-input label="DETAILS URL" name="details_url" type="url" />
                <label class="block"><span>Catatan</span><textarea name="notes" rows="3"></textarea></label>
                <button class="admin-form-primary" type="submit">Tambah shipment</button>
            </form>
        </section>
    </div>
</x-layouts.app>
