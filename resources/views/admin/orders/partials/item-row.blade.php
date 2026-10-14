<div data-item-row class="rounded-lg border border-zinc-200 bg-zinc-50/60 p-4">
    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
    <label class="mb-3 block"><span class="text-xs font-medium text-zinc-600">Produk Batch</span>
        <select data-native-select data-item-product name="items[{{ $index }}][product_id]">
            <option value="">Item manual</option>
            @foreach($batches as $productBatch)
                @foreach($productBatch->products as $product)
                    <option value="{{ $product->id }}" data-batch="{{ $productBatch->id }}" data-name="{{ $product->name }}" data-variant="{{ $product->variant }}" data-price="{{ $product->pivot->full_price ?? $product->default_price }}" @selected($selectedBatchId === $productBatch->id && ($item['product_id'] ?? null) == $product->id) @disabled($selectedBatchId !== $productBatch->id) @if($selectedBatchId !== $productBatch->id) hidden @endif>{{ $product->name }}{{ $product->variant ? ' · '.$product->variant : '' }}</option>
                @endforeach
            @endforeach
        </select>
    </label>
    <div class="grid gap-3 {{ $order->exists ? 'md:grid-cols-[1.6fr_1fr_.5fr_.8fr_1fr_auto]' : 'md:grid-cols-[1.8fr_1fr_.5fr_.8fr_auto]' }} md:items-end">
        <label class="block"><span class="text-xs font-medium text-zinc-600">Nama item</span><input name="items[{{ $index }}][item_name]" value="{{ $item['item_name'] ?? '' }}" required placeholder="Contoh: NCT DREAM Official Photocard"></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Varian</span><input name="items[{{ $index }}][variant]" value="{{ $item['variant'] ?? '' }}" placeholder="Opsional"></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Qty</span><input data-item-qty name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $item['quantity'] ?? 1 }}" required></label>
        <label class="block"><span class="text-xs font-medium text-zinc-600">Harga satuan</span><input name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}"></label>
        @if($order->exists)
            <label class="block"><span class="text-xs font-medium text-zinc-600">Status item</span><select name="items[{{ $index }}][override_status_id]"><option value="">Ikuti pesanan</option>@foreach($itemStatuses as $status)<option value="{{ $status->id }}" @selected(($item['override_status_id'] ?? null) == $status->id)>{{ $status->name }}</option>@endforeach</select></label>
        @endif
        <button type="button" data-remove-item class="admin-form-danger-action">Hapus</button>
    </div>
    <label class="mt-3 block"><span class="text-xs font-medium text-zinc-600">Catatan item</span><input name="items[{{ $index }}][notes]" value="{{ $item['notes'] ?? '' }}" placeholder="Opsional, misalnya preferensi member"></label>
</div>
