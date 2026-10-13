<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        try {
            Password::sendResetLink($data + ['role' => [User::ROLE_ADMIN, User::ROLE_CUSTOMER]]);
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['email' => 'Email reset belum dapat dikirim. Silakan coba lagi nanti.']);
        }

        // Give the same response for unknown accounts and recently requested links.
        return back()->with('status', 'Jika email terdaftar, tautan reset password akan dikirim. Periksa inbox atau folder spam. Untuk meminta ulang, tunggu 60 detik.');
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function update(ResetPasswordRequest $request)
    {
        $status = DB::transaction(fn () => Password::reset(
            $request->safe()->only(['email', 'token', 'password', 'password_confirmation'])
                + ['role' => [User::ROLE_ADMIN, User::ROLE_CUSTOMER]],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'password_set_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        ));

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.']);
        }

        return redirect()->route('login')->with('status', 'Password berhasil diubah. Silakan login dengan password baru.');
    }
}
