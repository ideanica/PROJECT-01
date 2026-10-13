<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberRequest;
use App\Models\CustomerGroup;
use App\Models\Member;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $members = Member::query()
            ->with(['customerGroup', 'user'])
            ->withCount('orders')
            ->when(request('q'), function ($query, $q) {
                $query->where(function ($query) use ($q) {
                    $query->where('display_name', 'like', "%{$q}%")
                        ->orWhere('member_code', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when(request('customer_group_id'), fn ($query, $id) => $query->where('customer_group_id', $id))
            ->when(request('registration') === 'pending', fn ($query) => $query->whereHas('user', fn ($user) => $user->where('registration_pending', true)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.members.index', ['members' => $members, 'groups' => CustomerGroup::withCount('members')->orderBy('name')->get(), 'pendingCount' => User::where('registration_pending', true)->count()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.members.form', ['member' => new Member, 'groups' => $this->groups()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMemberRequest $request, CustomerAccountService $accounts)
    {
        DB::transaction(function () use ($request, $accounts): void {
            $member = Member::create(Arr::except($request->validated(), ['password']) + [
                'member_code' => $this->generateMemberCode(),
                'is_active' => true,
            ]);
            $accounts->sync($member, $request->input('password'));
            if ($member->user?->registration_pending) {
                $member->update(['is_active' => false]);
            }
        });

        session()->flash('status', 'Pelanggan berhasil ditambahkan.');

        return new RedirectResponse('/admin/members', 303);
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member)
    {
        $member->load(['customerGroup', 'user', 'orders.batch.currentStatus', 'orders.overrideStatus', 'orders.paymentStatus', 'orders.items']);

        return view('admin.members.show', ['member' => $member, 'groups' => CustomerGroup::where('is_active', true)->orderBy('name')->get()]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        return view('admin.members.form', ['member' => $member, 'groups' => $this->groups($member)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreMemberRequest $request, Member $member, CustomerAccountService $accounts)
    {
        DB::transaction(function () use ($request, $member, $accounts): void {
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();
            $member->update(Arr::except($request->validated(), ['password']) + ['is_active' => $request->boolean('is_active')]);
            $accounts->sync($member, $request->input('password'));
            if ($member->user?->registration_pending) {
                $member->update(['is_active' => false]);
            }
        });

        session()->flash('status', 'Pelanggan berhasil diperbarui.');

        return new RedirectResponse('/admin/members/'.$member->id, 303);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        $member->update(['is_active' => false]);

        return back()->with('status', 'Pelanggan dinonaktifkan.');
    }

    private function generateMemberCode(): string
    {
        do {
            $code = 'CUS-'.Str::upper(Str::random(8));
        } while (Member::where('member_code', $code)->exists());

        return $code;
    }

    private function groups(?Member $member = null)
    {
        return CustomerGroup::query()->where(fn ($query) => $query->where('is_active', true)
            ->when($member?->customer_group_id, fn ($query, $id) => $query->orWhere('id', $id)))
            ->orderBy('name')->get();
    }
}
