<div data-item-row class="rounded-lg border border-zinc-200 bg-zinc-50/60 p-4">
    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
    @if(!empty($item['product_id']))<input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] }}">@endif
    <div class="grid gap-3 {{ $order->exists ? 'md:grid-cols-2 xl:grid-cols-[1.5fr_1fr_.5fr_.8fr_1.2fr_1fr_auto]' : 'md:grid-cols-2 xl:grid-cols-[1.5fr_1fr_.5fr_.8fr_1.2fr_auto]' }} md:items-end">
        <label class="block"><span class="text-xs font-medium text-zinc-600">Nama item</span><input name="items[{{ $index }}][item_name]" value="{{ $item['item_name'] ?? '' }}" required placeholder="Contoh: NCT DREAM Official Photocard"></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Varian / Member</span><input name="items[{{ $index }}][variant]" value="{{ $item['variant'] ?? '' }}" placeholder="Opsional"></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Qty</span><input data-item-qty name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $item['quantity'] ?? 1 }}" required></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Harga satuan</span><input name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}"></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Shipment</span><select name="items[{{ $index }}][shipment_group_id]"><option value="">Belum ditentukan</option>@foreach($shipmentGroups as $shipment)<option value="{{ $shipment->id }}" @selected(($item['shipment_group_id'] ?? null) == $shipment->id)>{{ $shipment->batch->batch_number }} · {{ $shipment->name }}</option>@endforeach</select></label>
        @if($order->exists)
            <label class="block"><span class="text-xs font-medium text-zinc-600">Status item</span><select name="items[{{ $index }}][override_status_id]"><option value="">Ikuti pesanan</option>@foreach($itemStatuses as $status)<option value="{{ $status->id }}" @selected(($item['override_status_id'] ?? null) == $status->id)>{{ $status->name }}</option>@endforeach</select></label>
        @endif
        <button type="button" data-remove-item class="admin-form-danger-action">Hapus</button>
    </div>
    <label class="mt-3 block"><span class="text-xs font-medium text-zinc-600">Keterangan / catatan item</span><input name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? '' }}" placeholder="Opsional, misalnya preferensi member"></label>
</div>
