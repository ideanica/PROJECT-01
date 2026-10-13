<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerAccountService
{
    public function sync(Member $member, ?string $password = null): void
    {
        $user = $member->user;
        if (! $user && ! $password) {
            return;
        }
        if ($user?->isAdmin()) {
            throw ValidationException::withMessages(['username' => 'Akun admin tidak dapat diubah menjadi customer.']);
        }
        if (! $member->email) {
            throw ValidationException::withMessages(['email' => 'Email wajib diisi untuk menyiapkan akun login customer.']);
        }

        $user ??= new User;
        $user->fill([
            'name' => $member->display_name,
            'username' => $member->username,
            'email' => $member->email,
            'member_id' => $member->id,
        ]);
        $user->role = 'customer';
        if ($password) {
            $user->password = $password;
            $user->password_set_at = now();
            $user->remember_token = Str::random(60);
        }
        $user->save();
    }
}
