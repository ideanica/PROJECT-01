<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCustomerRequest;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisteredCustomerController extends Controller
{
    public function create()
    {
        return view('auth.register', [
            'groups' => CustomerGroup::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(RegisterCustomerRequest $request)
    {
        $data = $request->validated();
        $data['customer_group_id'] ??= null;

        $mode = DB::transaction(function () use ($data): string {
            $group = $data['customer_group_id']
                ? CustomerGroup::whereKey($data['customer_group_id'])->lockForUpdate()->first()
                : null;

            if ($data['customer_group_id'] && ! $group?->is_active) {
                throw ValidationException::withMessages(['customer_group_id' => 'Group tidak tersedia. Pilih group aktif.']);
            }

            $candidates = Member::query()
                ->where(fn ($query) => $query
                    ->where('username', $data['username'])
                    ->orWhere('email', $data['email']))
                ->lockForUpdate()
                ->get();

            if ($candidates->count() > 1) {
                throw ValidationException::withMessages([
                    'username' => 'Username dan email mengarah ke data buyer yang berbeda. Hubungi admin untuk menghubungkan akun secara manual.',
                ]);
            }

            $member = $candidates->first();
            $isClaim = $member !== null;

            if ($member) {
                if ($member->user()->exists()) {
                    throw ValidationException::withMessages([
                        'username' => 'Data buyer ini sudah mempunyai akun login.',
                    ]);
                }

                if ($member->username && $member->username !== $data['username']) {
                    throw ValidationException::withMessages([
                        'username' => 'Username tidak cocok dengan data buyer lama. Hubungi admin untuk verifikasi claim.',
                    ]);
                }

                if ($member->email && $member->email !== $data['email']) {
                    throw ValidationException::withMessages([
                        'email' => 'Email tidak cocok dengan data buyer lama. Hubungi admin untuk verifikasi claim.',
                    ]);
                }

                $member->fill([
                    'username' => $member->username ?: $data['username'],
                    'email' => $member->email ?: $data['email'],
                ]);
                $member->save();
            } else {
                $member = Member::create([
                    'member_code' => 'CUS-'.Str::upper(Str::random(12)),
                    'display_name' => $data['name'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'customer_group_id' => $data['customer_group_id'],
                    'is_active' => $data['customer_group_id'] !== null,
                ]);
            }

            $user = new User([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'member_id' => $member->id,
            ]);
            $user->role = 'customer';
            // Claim history selalu membutuhkan approval admin. Member tetap aktif untuk pencatatan order admin.
            $user->registration_pending = $isClaim || $data['customer_group_id'] === null;
            $user->password_set_at = now();
            $user->save();

            return $isClaim ? 'claim' : ($user->registration_pending ? 'pending' : 'ready');
        });

        $message = match ($mode) {
            'claim' => 'Akun ditemukan cocok dengan data buyer lama. Claim history sudah dibuat dan menunggu persetujuan admin sebelum login.',
            'pending' => 'Permintaan registrasi diterima. Tunggu persetujuan admin untuk menentukan group. Kamu belum bisa login sampai akun disetujui.',
            default => 'Akun berhasil dibuat. Silakan login menggunakan username dan password.',
        };

        return redirect()->route('login')->with('status', $message);
    }
}
