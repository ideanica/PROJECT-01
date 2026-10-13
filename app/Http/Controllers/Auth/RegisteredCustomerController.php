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
        DB::transaction(function () use ($data): void {
            $group = $data['customer_group_id'] ? CustomerGroup::whereKey($data['customer_group_id'])->lockForUpdate()->first() : null;
            if ($data['customer_group_id'] && ! $group?->is_active) {
                throw ValidationException::withMessages(['customer_group_id' => 'Group tidak tersedia. Pilih group aktif.']);
            }
            $member = Member::create([
                'member_code' => 'CUS-'.Str::upper(Str::random(12)),
                'display_name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'customer_group_id' => $data['customer_group_id'],
                'is_active' => $data['customer_group_id'] !== null,
            ]);
            $user = new User([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'member_id' => $member->id,
            ]);
            $user->role = 'customer';
            $user->registration_pending = $data['customer_group_id'] === null;
            $user->password_set_at = now();
            $user->save();
        });

        return redirect()->route('login')->with('status', $data['customer_group_id'] === null
            ? 'Permintaan registrasi diterima. Tunggu persetujuan admin untuk menentukan group. Kamu belum bisa login sampai akun disetujui.'
            : 'Akun berhasil dibuat. Silakan login menggunakan username LINE dan password.');
    }
}
