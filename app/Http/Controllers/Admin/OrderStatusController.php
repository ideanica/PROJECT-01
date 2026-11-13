<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderStatusRequest;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class OrderStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Dua folder status dipaginasi terpisah agar tab tetap bisa berpindah tanpa memuat ulang halaman.
        $trackingStatuses = $this->statusQuery()
            ->where('scope', '!=', 'payment')
            ->orderBy('sequence')
            ->orderBy('name')
            ->paginate(10, ['*'], 'tracking_page')
            ->withQueryString()
            ->appends(['scope' => 'tracking']);

        $paymentStatuses = $this->statusQuery()
            ->where('scope', 'payment')
            ->orderBy('sequence')
            ->orderBy('name')
            ->paginate(10, ['*'], 'payment_page')
            ->withQueryString()
            ->appends(['scope' => 'payment']);

        return view('admin.statuses.index', compact('trackingStatuses', 'paymentStatuses'));
    }

    private function statusQuery()
    {
        return OrderStatus::query()
            ->withCount(['batches', 'memberOrders', 'orderItems', 'paymentMemberOrders', 'oldHistories', 'newHistories'])
            ->when(request('q'), function ($query, $term) {
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%"));
            })
            ->when(request('active') === '1', fn ($query) => $query->where('is_active', true))
            ->when(request('active') === '0', fn ($query) => $query->where('is_active', false));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.statuses.form', ['orderStatus' => new OrderStatus]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderStatusRequest $request)
    {
        $data = $request->validated();
        $data['code'] = $this->generateCode($data['name']);
        $data['sequence'] = $this->nextSequence($data['scope']);
        $data['is_initial'] = $request->boolean('is_initial');
        $data['is_final'] = $request->boolean('is_final');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['locks_order_editing'] = $this->locksOrderEditing($request);

        $orderStatus = OrderStatus::create($data);

        session()->flash('status', 'Status berhasil ditambahkan.');

        return new RedirectResponse('/admin/order-statuses/'.$orderStatus->id, 303);
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderStatus $orderStatus)
    {
        $orderStatus->loadCount(['batches', 'memberOrders', 'orderItems', 'paymentMemberOrders', 'oldHistories', 'newHistories']);

        return view('admin.statuses.show', compact('orderStatus'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderStatus $orderStatus)
    {
        return view('admin.statuses.form', compact('orderStatus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreOrderStatusRequest $request, OrderStatus $orderStatus)
    {
        $data = $request->validated();
        $data['sequence'] = $orderStatus->scope === $data['scope']
            ? $orderStatus->sequence
            : $this->nextSequence($data['scope']);
        $data['is_initial'] = $request->boolean('is_initial');
        $data['is_final'] = $request->boolean('is_final');
        $data['is_active'] = $request->boolean('is_active');
        $data['locks_order_editing'] = $this->locksOrderEditing($request);

        $orderStatus->update($data);

        session()->flash('status', 'Status berhasil diperbarui.');

        return new RedirectResponse('/admin/order-statuses/'.$orderStatus->id, 303);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderStatus $orderStatus)
    {
        if ($orderStatus->usedCount() > 0) {
            $orderStatus->update(['is_active' => false]);

            return back()->with('status', 'Status sudah digunakan, jadi dinonaktifkan.');
        }

        $orderStatus->delete();
        session()->flash('status', 'Status dihapus.');

        return new RedirectResponse('/admin/order-statuses?scope='.urlencode($orderStatus->scope), 303);
    }

    private function nextSequence(string $scope): int
    {
        // Status terminal bawaan tidak menentukan posisi status operasional baru.
        $query = OrderStatus::query()->whereNotIn('code', ['refunded', 'selesai']);

        if ($scope === 'all') {
            $query->whereIn('scope', ['batch', 'member_order', 'order_item', 'all']);
        } elseif ($scope === 'payment') {
            $query->where('scope', 'payment');
        } else {
            $query->whereIn('scope', [$scope, 'all']);
        }

        $currentMaximum = (int) ($query->max('sequence') ?? 0);

        return ((int) floor($currentMaximum / 10) + 1) * 10;
    }

    private function generateCode(string $name): string
    {
        $baseCode = Str::slug(Str::limit($name, 220, '')) ?: 'status';
        $code = $baseCode;
        $suffix = 2;

        while (OrderStatus::where('code', $code)->exists()) {
            $code = $baseCode.'-'.$suffix++;
        }

        return $code;
    }

    private function locksOrderEditing(StoreOrderStatusRequest $request): bool
    {
        return in_array($request->string('scope')->toString(), ['batch', 'all'], true)
            && $request->boolean('locks_order_editing');
    }
}
