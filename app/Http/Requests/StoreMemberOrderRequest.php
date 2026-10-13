<?php

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\Product;
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

        $this->merge(['items' => $items]);

        if ($this->filled('ems_tax_amount') && $this->input('ems_tax_status', 'not_billed') === 'not_billed') {
            $this->merge(['ems_tax_status' => 'unpaid']);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $order = $this->route('member_order');
        $currentPaymentStatusId = $this->route('member_order')?->payment_status_id;
        $isExistingOrder = $order !== null;
        $itemsAreLocked = $order
            ? ($order->loadMissing(['batch.currentStatus', 'overrideStatus', 'paymentStatus'])->batch?->orders_locked
                || $order->is_refunded)
            : false;

        return [
            'order_code' => ['prohibited'],
            // member_id wajib untuk NEW order, harus merujuk ke Member yang akun customer siap + aktif
            'member_id' => ['required', 'exists:members,id'],
            // customer_name dan customer_username tidak lagi digunakan untuk pembuatan buyer baru
            'customer_name' => ['prohibited'],
            'customer_username' => ['prohibited'],
            'batch_id' => $itemsAreLocked
                ? ['required', Rule::in([$order->batch_id])]
                : ['required', 'exists:batches,id'],
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
            'items.*.product_id' => [
                'nullable',
                'exists:products,id',
            ],
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
            function ($validator) {
                // Validasi tambahan: member harus akun customer siap dan aktif untuk NEW order
                if ($this->filled('member_id') && ! $this->route('member_order')) {
                    $member = Member::find($this->integer('member_id'));
                    if ($member && ! $member->isEligibleForNewOrder()) {
                        $validator->errors()->add('member_id', 'Member tidak eligible untuk order baru. Member harus memiliki akun customer dengan password dan aktif.');
                    }
                }
            },
        ];
    }
}
