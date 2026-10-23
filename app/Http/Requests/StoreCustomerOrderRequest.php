<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerOrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && (int) ($item['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_CUSTOMER
            && $this->user()?->member_id !== null;
    }

    public function rules(): array
    {
        return [
            'payment_type' => ['required', Rule::in(['dp', 'full'])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
