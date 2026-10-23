<?php

namespace App\Http\Requests;

use App\Services\BatchEmsTaxService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBatchRequest extends FormRequest
{
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
        return [
            ...BatchEmsTaxService::rules(),
            'batch_name' => ['nullable', 'string', 'max:255'],
            'current_status_id' => ['nullable', 'exists:order_statuses,id'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'catalog_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_catalog_image' => ['sometimes', 'boolean'],
            'ordering_deadline' => ['nullable', 'date'],
            'is_catalog_visible' => ['sometimes', 'boolean'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'variants' => ['nullable', 'array', 'max:50'],
            'variants.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            // Retained as an optional legacy field; DP is calculated from the global setting.
            'variants.*.dp_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.full_price' => ['required', 'numeric', 'min:0'],
            'variants.*.is_available' => ['sometimes', 'boolean'],
            'status_note' => ['nullable', 'string', 'max:1000'],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_archived' => $this->route('batch') ? ['sometimes', 'boolean'] : ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'catalog_image.image' => 'File foto batch harus berupa gambar yang valid.',
            'catalog_image.mimes' => 'Format foto batch harus JPG, JPEG, PNG, atau WEBP.',
            'catalog_image.max' => 'Ukuran foto batch maksimal 4 MB.',
        ];
    }

}
