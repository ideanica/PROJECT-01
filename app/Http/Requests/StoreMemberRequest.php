<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreMemberRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'phone' => trim((string) $this->input('phone')),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-admin') === true;
    }

    public function messages(): array
    {
        return ['username.unique' => 'Username LINE sudah digunakan. Silakan pilih username LINE lain.'];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $memberId = $this->route('member')?->id;
        $userId = $this->route('member')?->user?->id;
        $currentGroupId = $this->route('member')?->customer_group_id;

        return [
            'display_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('members')->ignore($memberId), Rule::unique('users')->ignore($userId)],
            'email' => [$userId || $this->filled('password') ? 'required' : 'nullable', 'email', 'max:255', Rule::unique('users')->ignore($userId), Rule::unique('members')->ignore($memberId)],
            'customer_group_id' => ['nullable', Rule::exists('customer_groups', 'id')->where(fn ($query) => $query->where('is_active', true)->when($currentGroupId, fn ($query) => $query->orWhere('id', $currentGroupId)))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string'],
            'line_user_id' => ['prohibited'],
            'is_active' => $this->route('member') ? ['sometimes', 'boolean'] : ['prohibited'],
        ];
    }
}
