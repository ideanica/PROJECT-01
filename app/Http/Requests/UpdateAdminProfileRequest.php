<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id), Rule::unique('members')->ignore($this->user()->member_id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'current_password' => [$this->filled('password') || $this->input('email') !== $this->user()->email ? 'required' : 'nullable', 'current_password'],
            'role' => ['prohibited'],
            'member_id' => ['prohibited'],
            'username' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['current_password.required' => 'Masukkan password saat ini untuk mengganti email atau password.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.'];
    }
}
