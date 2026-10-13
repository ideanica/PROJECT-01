<?php

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\Product;
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

        $products = Product::query()
            ->whereIn('id', $rawItems->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $items = $rawItems
            ->map(function (array $item) use ($products): array {
                $product = $products->get($item['product_id'] ?? null);

                if ($product) {
                    $item['item_name'] = $product->name;
                    $item['variant'] = $product->variant;
                    $item['unit_price'] = filled($item['unit_price'] ?? null)
                        ? $item['unit_price']
                        : $product->default_price;
                }

                return $item;
            })
            ->values()
            ->all();

        $this->merge([
            'go_number' => $this->filled('go_number') ? trim((string) $this->input('go_number')) : null,
            'items' => $items,
        ]);

        if ($this->filled('ems_tax_amount') && $this->input('ems_tax_status', 'not_billed') === 'not_billed') {
            $this->merge(['ems_tax_status' => 'unpaid']);
        }
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
        $itemsAreLocked = $order
            ? ($order->loadMissing(['batch.currentStatus', 'overrideStatus', 'paymentStatus'])->batch?->orders_locked
                || $order->is_refunded)
            : false;

        return [
            'order_code' => ['prohibited'],
            'member_id' => ['required', Rule::exists('members', 'id')->where('is_active', true)],
            'customer_name' => ['prohibited'],
            'customer_username' => ['prohibited'],
            'batch_id' => $itemsAreLocked
                ? ['required', Rule::in([$order->batch_id])]
                : ['required', 'exists:batches,id'],
            'go_number' => ['nullable', 'string', 'max:50'],
            'override_status_id' => ['prohibited'],
            'payment_status_id' => [
                'nullable',
                Rule::exists('order_statuses', 'id')->where(fn ($query) => $query
                    ->where('scope', 'payment')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->when(! $isExistingOrder, fn ($query) => $query->where('code', '!=', 'refund'))
                        ->when($currentPaymentStatusId, fn ($query, $statusId) => $query->orWhere('id', $statusId)))),
            ],
            'notes' => ['nullable', 'string'],
            'ems_tax_amount' => ['nullable', 'required_if:ems_tax_status,unpaid,paid', 'numeric', 'min:1'],
            'ems_tax_status' => ['nullable', Rule::in(['not_billed', 'unpaid', 'paid'])],
            'ems_tax_due_date' => ['nullable', 'date'],
            'ems_tax_notes' => ['nullable', 'string', 'max:1000'],
            'items' => $itemsAreLocked ? ['prohibited'] : ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'exists:order_items,id'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
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
                    if ($member && ! $member->isAvailableForOrder()) {
                        $validator->errors()->add('member_id', 'Member nonaktif tidak dapat digunakan untuk order baru.');
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
