<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdminProfileRequest;
use Illuminate\Support\Str;

class AdminProfileController extends Controller
{
    public function update(UpdateAdminProfileRequest $request)
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));
        if ($request->filled('password')) {
            $user->password = $request->validated('password');
            $user->password_set_at = now();
            $user->remember_token = Str::random(60);
        }
        $user->save();
        $request->session()->put('auth_identity', $user->sessionIdentity());

        return redirect()->route('profile.detail')->with('status', 'Profil admin berhasil diperbarui.');
    }
}
