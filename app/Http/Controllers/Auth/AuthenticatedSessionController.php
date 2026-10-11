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
        $request->merge(['username' => mb_strtolower(trim((string) $request->input('username', $request->input('email'))))]);
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $field = str_contains($data['username'], '@') ? 'email' : 'username';
        $credentials = [$field => $data['username'], 'password' => $data['password']];

        if (! Auth::attemptWhen(
            $credentials,
            fn ($user) => $user->isAdmin() || ($user->role === 'customer' && ! $user->registration_pending && $user->password_set_at !== null && $user->member?->is_active),
            $request->boolean('remember')
        )) {
            throw ValidationException::withMessages([
                'username' => 'Username LINE/email atau password tidak sesuai, atau akun belum aktif. Jika belum memilih group saat registrasi, tunggu persetujuan admin.',
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
