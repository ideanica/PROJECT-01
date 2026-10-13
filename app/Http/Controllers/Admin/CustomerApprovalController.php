<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerApprovalController extends Controller
{
    public function store(Request $request, Member $member)
    {
        $data = $request->validate([
            'customer_group_id' => ['required', Rule::exists('customer_groups', 'id')->where('is_active', true)],
        ]);

        DB::transaction(function () use ($member, $data): void {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $user = User::where('member_id', $member->id)->lockForUpdate()->first();
            abort_unless($user && $user->role === 'customer' && $user->registration_pending, 409, 'Permintaan ini sudah diproses atau bukan registrasi tertunda.');
            $group = CustomerGroup::whereKey($data['customer_group_id'])->lockForUpdate()->first();
            if (! $group?->is_active) {
                throw ValidationException::withMessages(['customer_group_id' => 'Pilih group yang masih aktif.']);
            }
            $member->update(['customer_group_id' => $group->id, 'is_active' => true]);
            $user->registration_pending = false;
            $user->save();
        });

        return back()->with('status', 'Registrasi disetujui dan group ditentukan. Customer sekarang dapat login.');
    }
}
