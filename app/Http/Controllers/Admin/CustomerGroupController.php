<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerGroupRequest;
use App\Models\CustomerGroup;
use App\Models\User;

class CustomerGroupController extends Controller
{
    public function index()
    {
        return view('admin.groups.index', [
            'pendingCount' => User::where('registration_pending', true)->count(),
            'groups' => CustomerGroup::withCount('members')->orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('admin.groups.form', ['group' => new CustomerGroup]);
    }

    public function store(StoreCustomerGroupRequest $request)
    {
        $group = CustomerGroup::create($request->validated());

        return redirect()->route('admin.customer-groups.show', $group)->with('status', 'Group berhasil ditambahkan.');
    }

    public function show(CustomerGroup $customerGroup)
    {
        return view('admin.groups.show', [
            'group' => $customerGroup,
            'members' => $customerGroup->members()->with('user')->withCount('orders')->orderBy('display_name')->paginate(15),
        ]);
    }

    public function edit(CustomerGroup $customerGroup)
    {
        return view('admin.groups.form', ['group' => $customerGroup]);
    }

    public function update(StoreCustomerGroupRequest $request, CustomerGroup $customerGroup)
    {
        $customerGroup->update($request->validated());

        return redirect()->route('admin.customer-groups.show', $customerGroup)->with('status', 'Group berhasil diperbarui.');
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        // Preserve membership and historical labels. Re-enable through the edit form.
        $customerGroup->update(['is_active' => false]);

        return back()->with('status', 'Group dinonaktifkan. Customer yang sudah tergabung tetap tersimpan.');
    }
}
