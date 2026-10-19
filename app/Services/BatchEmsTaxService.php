<?php

namespace App\Services;

use App\Models\Batch;
use Illuminate\Support\Facades\DB;

class BatchEmsTaxService
{
    public static function rules(): array
    {
        return [
            'ems_tax_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'ems_tax_due_date' => ['nullable', 'date'],
            'ems_tax_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function sync(Batch $batch, bool $clear = false): void
    {
        DB::transaction(function () use ($batch, $clear): void {
            $batch = Batch::query()->lockForUpdate()->findOrFail($batch->id);

            // Leave historical individual bills intact until the batch is explicitly configured.
            if ($batch->ems_tax_amount === null && ! $clear) {
                return;
            }

            $orders = $batch->orders()->orderBy('member_id')->orderBy('id')->lockForUpdate()->get();
            $count = $orders->count();
            if ($count === 0) {
                return;
            }

            $totalCents = (int) round((float) ($batch->ems_tax_amount ?? 0) * 100);
            $share = intdiv($totalCents, $count);
            $remainder = $totalCents % $count;

            foreach ($orders as $index => $order) {
                $cents = $share + ($index < $remainder ? 1 : 0);
                $amount = number_format($cents / 100, 2, '.', '');
                $amountChanged = $order->ems_tax_amount !== $amount;
                $order->forceFill([
                    'ems_tax_amount' => $batch->ems_tax_amount === null ? null : $amount,
                    'ems_tax_status' => $cents === 0 ? 'not_billed'
                        : (! $amountChanged && $order->ems_tax_status === 'paid' ? 'paid' : 'unpaid'),
                    'ems_tax_due_date' => $batch->ems_tax_due_date,
                    'ems_tax_notes' => $batch->ems_tax_notes,
                ]);
                if ($order->isDirty()) {
                    $order->save();
                }
            }
        });
    }
}
