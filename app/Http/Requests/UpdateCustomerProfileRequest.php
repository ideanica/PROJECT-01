<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'customer' && $this->user()?->member?->isEligibleForNewOrder();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_name' => trim((string) $this->input('display_name')),
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')) ?: null,
            'address' => trim((string) $this->input('address')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $member = $this->user()->member;

        return [
            'display_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9][a-z0-9._-]*$/', Rule::unique('members')->ignore($member->id), Rule::unique('users')->ignore($this->user()->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('members')->ignore($member->id), Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\s-]+$/'],
            'address' => ['nullable', 'string', 'max:2000'],
            'profile_photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_photo' => ['sometimes', 'boolean'],
            'customer_group_id' => ['prohibited'],
            'role' => ['prohibited'],
            'is_active' => ['prohibited'],
            'member_id' => ['prohibited'],
            'line_user_id' => ['prohibited'],
            'notes' => ['prohibited'],
            'password' => ['prohibited'],
            'registration_pending' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username LINE sudah digunakan. Silakan pilih username LINE lain.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka dan tanda +, -, titik, atau kurung.',
            'profile_photo.image' => 'File harus berupa gambar yang valid.',
            'profile_photo.mimes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'profile_photo.max' => 'Ukuran foto maksimal 2 MB.',
            'profile_photo.dimensions' => 'Dimensi foto maksimal 4096 × 4096 piksel.',
        ];
    }
}
