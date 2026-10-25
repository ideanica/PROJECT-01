<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $identifierField = $request->filled('email') ? 'email' : 'username';
        $request->merge([$identifierField => mb_strtolower(trim((string) $request->input($identifierField)))]);
        $data = $request->validate([
            $identifierField => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $field = str_contains($data[$identifierField], '@') ? 'email' : 'username';
        $credentials = [$field => $data[$identifierField], 'password' => $data['password']];

        if (! Auth::attemptWhen(
            $credentials,
            fn ($user) => $user->isAdmin() || ($user->role === 'customer' && ! $user->registration_pending && $user->password_set_at !== null && $user->member?->is_active),
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                $identifierField => 'Email atau password tidak sesuai, atau akun belum aktif. Jika akun menunggu persetujuan, hubungi admin.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put('login_at', now()->timestamp);
        $request->session()->put('auth_identity', Auth::user()->sessionIdentity());

        $request->session()->forget('url.intended');

        return redirect()->route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'profile.show');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new RedirectResponse(route('login', [], false), 303);
    }
}
