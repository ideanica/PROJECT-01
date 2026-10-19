<div class="grid items-start gap-4 sm:grid-cols-2">
    <x-text-input label="Total pajak EMS batch (opsional)" name="ems_tax_amount" type="number" min="0" max="999999999999.99" step="0.01" :value="old('ems_tax_amount', $batch->ems_tax_amount)" placeholder="Contoh: 150000" />
    <x-text-input label="Jatuh tempo EMS" name="ems_tax_due_date" type="date" :value="old('ems_tax_due_date', $batch->ems_tax_due_date?->format('Y-m-d'))" />
</div>
<label class="mt-4 block"><span>Rincian pajak EMS</span><textarea name="ems_tax_notes" rows="3" maxlength="1000">{{ old('ems_tax_notes', $batch->ems_tax_notes) }}</textarea></label>
<p class="admin-form-help mt-3">Boleh dikosongkan dan diisi nanti dari detail batch. Total dibagi rata ke seluruh pelanggan dalam batch, terlepas dari jumlah item. Pembagian dihitung ulang saat pelanggan berubah. Selisih pembulatan maksimal Rp 0,01 per pelanggan. Jika nominal bagian berubah, status EMS kembali menunggu pembayaran.</p>
