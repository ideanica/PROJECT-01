<?php

namespace App\Http\Requests;

use App\Models\Batch;
use App\Models\Member;
use App\Models\ShipmentGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $rawItems = collect($this->input('items', []))
            ->filter(fn ($item) => filled($item['product_id'] ?? null) || filled($item['item_name'] ?? null));

        $products = Batch::find($this->integer('batch_id'))?->products()
            ->whereIn('id', $rawItems->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id') ?? collect();

        $items = $rawItems
            ->map(function (array $item) use ($products): array {
                $product = $products->get($item['product_id'] ?? null);

                if ($product) {
                    $item['item_name'] = $product->name;
                    $item['variant'] = $product->variant;
                    $item['unit_price'] = filled($item['unit_price'] ?? null)
                        ? $item['unit_price']
                        : ($product->pivot->full_price ?? $product->default_price);
                }

                return $item;
            })
            ->values()
            ->all();

        $this->merge([
            'go_number' => $this->filled('go_number') ? trim((string) $this->input('go_number')) : null,
            'items' => $items,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') === true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $order = $this->route('member_order');
        $currentPaymentStatusId = $order?->payment_status_id;
        $isExistingOrder = $order !== null;
        $hasPayments = $order?->payments()->exists() ?? false;
        $itemsAreLocked = $order
            ? ($order->loadMissing(['batch.currentStatus', 'overrideStatus', 'paymentStatus'])->batch?->orders_locked
                || $order->is_refunded
                || $hasPayments)
            : false;

        $keepsExistingBuyer = $order && (int) $order->member_id === $this->integer('member_id')
            && (int) $order->batch_id === $this->integer('batch_id');

        return [
            'order_code' => ['prohibited'],
            // Keep processed orders attached to their buyer and prevent duplicate buyer/batch orders.
            'member_id' => $itemsAreLocked
                ? ['required', Rule::in([$order->member_id])]
                : ['required', Rule::exists('members', 'id')->when(! $keepsExistingBuyer, fn ($rule) => $rule->where('is_active', true)), Rule::unique('member_orders', 'member_id')->where('batch_id', $this->integer('batch_id'))->ignore($order?->id)],
            // customer_name dan customer_username tidak lagi digunakan untuk pembuatan buyer baru
            'customer_name' => ['prohibited'],
            'customer_username' => ['prohibited'],
            'batch_id' => $itemsAreLocked
                ? ['required', Rule::in([$order->batch_id])]
                : ['required', 'exists:batches,id'],
            'payment_type' => ! $isExistingOrder
                ? ['required', Rule::in(['dp', 'full'])]
                : ($hasPayments ? ['prohibited'] : ['nullable', Rule::in(['dp', 'full'])]),
            'go_number' => ['nullable', 'string', 'max:50'],
            'override_status_id' => ['prohibited'],
            'payment_status_id' => ['prohibited'],
            'notes' => ['nullable', 'string'],
            'ems_tax_amount' => ['prohibited'],
            'ems_tax_status' => ['prohibited'],
            'ems_tax_due_date' => ['prohibited'],
            'ems_tax_notes' => ['prohibited'],
            'items' => $itemsAreLocked ? ['prohibited'] : ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', Rule::exists('order_items', 'id')->where('member_order_id', $order?->id)],
            'items.*.product_id' => [
                'nullable',
                Rule::exists('batch_product', 'product_id')->where('batch_id', $this->integer('batch_id')),
            ],
            'items.*.shipment_group_id' => ['nullable', 'exists:shipment_groups,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.variant' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.override_status_id' => $isExistingOrder
                ? ['nullable', 'exists:order_statuses,id']
                : ['prohibited'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($this->filled('member_id')) {
                    $member = Member::find($this->integer('member_id'));
                    $order = $this->route('member_order');
                    $keepsExistingBuyer = $order && (int) $order->member_id === $this->integer('member_id')
                        && (int) $order->batch_id === $this->integer('batch_id');
                    if ($member && ! $keepsExistingBuyer && ! $member->isAvailableForOrder()) {
                        $validator->errors()->add('member_id', 'Pelanggan harus aktif dan memiliki akun member yang sudah disetujui serta siap login.');
                    }
                }

                $batchId = $this->integer('batch_id');
                if (! $batchId) {
                    return;
                }

                collect($this->input('items', []))
                    ->pluck('shipment_group_id')
                    ->filter()
                    ->unique()
                    ->each(function ($shipmentGroupId) use ($batchId, $validator): void {
                        $belongsToBatch = ShipmentGroup::query()
                            ->whereKey($shipmentGroupId)
                            ->where('batch_id', $batchId)
                            ->exists();

                        if (! $belongsToBatch) {
                            $validator->errors()->add('items', 'Shipment group item harus berasal dari Batch yang sama dengan pesanan.');
                        }
                    });
            },
        ];
    }
}
