<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveCustomer
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        // Old sessions may refer to a reused user ID after a database restore.
        if ($user && $request->session()->has(Auth::guard()->getName())
            && ! hash_equals($user->sessionIdentity(), (string) $request->session()->get('auth_identity', ''))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Sesi akun perlu diperbarui. Silakan login kembali.');
        }
        if ($user && ! $user->isAdmin() && $user->role !== 'customer') {
            abort(403);
        }
        if ($user && ! $user->isAdmin()
            && ($user->registration_pending || ! $user->member?->is_active || $user->password_set_at === null)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Akun belum aktif. Hubungi admin untuk menyiapkan akun login.']);
        }

        return $next($request);
    }
}
