<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberOrderRequest;
use App\Models\Batch;
use App\Models\Member;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Services\StatusTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MemberOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $orders = MemberOrder::query()
            ->with(['member', 'batch.currentStatus', 'overrideStatus', 'paymentStatus'])
            ->withCount('items')
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(fn ($query) => $query->where('order_code', 'like', "%{$q}%")
                    ->orWhereHas('member', fn ($query) => $query->where('display_name', 'like', "%{$q}%")->orWhere('username', 'like', "%{$q}%"))
                    ->orWhereHas('batch', fn ($query) => $query->where('batch_number', 'like', "%{$q}%")));
            })
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($request->boolean('problem'), function ($query) {
                $query->whereHas('overrideStatus', fn ($query) => $query->whereIn('status_type', ['failed', 'cancelled']));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.orders.form', $this->formData(new MemberOrder));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMemberOrderRequest $request, StatusTransitionService $statuses)
    {
        $order = DB::transaction(function () use ($request, $statuses) {
            // member_id sudah divalidasi oleh StoreMemberOrderRequest (required, exists, eligible)
            $member = Member::findOrFail($request->integer('member_id'));

            $order = MemberOrder::create($request->safe()->except([
                'order_code', 'override_status_id', 'items',
            ]) + [
                'member_id' => $member->id,
                'order_code' => 'TMP-'.Str::uuid(),
            ]);
            $order->forceFill(['order_code' => $this->generateOrderCode($order)])->save();
            $this->syncItems($order, $request->validated('items'), $statuses, $request->user());
            $order->update(['total_amount' => $order->items()->sum('subtotal')]);

            return $order;
        });

        session()->flash('status', 'Pesanan berhasil ditambahkan.');

        return new RedirectResponse('/admin/member-orders/'.$order->id, 303);
    }

    /**
     * Display the specified resource.
     */
    public function show(MemberOrder $memberOrder)
    {
        $memberOrder->load(['member', 'batch.currentStatus', 'overrideStatus', 'paymentStatus', 'items.product', 'items.overrideStatus', 'statusHistories.oldStatus', 'statusHistories.newStatus', 'statusHistories.changedBy']);
        $orderStatuses = OrderStatus::query()
            ->whereIn('scope', ['member_order', 'all'])
            ->where('code', '!=', 'refunded')
            ->where(fn ($query) => $query
                ->where('is_active', true)
                ->when($memberOrder->override_status_id, fn ($query, $statusId) => $query->orWhere('id', $statusId)))
            ->orderBy('sequence')
            ->orderBy('name')
            ->get();

        return view('admin.orders.show', [
            'order' => $memberOrder,
            'orderStatuses' => $orderStatuses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MemberOrder $memberOrder)
    {
        $memberOrder->load(['items', 'batch.currentStatus', 'overrideStatus']);

        return view('admin.orders.form', $this->formData($memberOrder));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreMemberOrderRequest $request, MemberOrder $memberOrder, StatusTransitionService $statuses)
    {
        DB::transaction(function () use ($request, $memberOrder, $statuses) {
            $memberOrder->loadMissing(['batch.currentStatus', 'overrideStatus', 'paymentStatus']);
            $itemsAreLocked = $memberOrder->batch->orders_locked || $memberOrder->is_refunded;

            // member_id sudah divalidasi - untuk update tetap memerlukan member_id yang valid
            $member = Member::findOrFail($request->integer('member_id'));

            $memberOrder->update($request->safe()->except([
                'order_code', 'override_status_id', 'items',
            ]) + ['member_id' => $member->id]);

            if (! $itemsAreLocked) {
                $this->syncItems($memberOrder, $request->validated('items'), $statuses, $request->user());
                $memberOrder->update(['total_amount' => $memberOrder->items()->sum('subtotal')]);
            }

            $paymentStatus = $request->filled('payment_status_id')
                ? OrderStatus::find($request->integer('payment_status_id'))
                : null;

            if ($paymentStatus?->code === 'refund' && $memberOrder->overrideStatus?->code !== 'refunded') {
                $refundedStatus = OrderStatus::query()
                    ->where('code', 'refunded')
                    ->where('scope', 'member_order')
                    ->where('is_active', true)
                    ->first();

                if (! $refundedStatus) {
                    throw ValidationException::withMessages([
                        'payment_status_id' => 'Status terminal Refunded belum tersedia pada Manajemen Status.',
                    ]);
                }

                $statuses->transition(
                    $memberOrder,
                    $refundedStatus,
                    $request->user(),
                    'Progress pesanan dihentikan otomatis karena pembayaran diubah menjadi refund.'
                );
            }
        });

        session()->flash('status', 'Pesanan berhasil diperbarui.');

        return new RedirectResponse('/admin/member-orders/'.$memberOrder->id, 303);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MemberOrder $memberOrder)
    {
        $memberOrder->delete();

        session()->flash('status', 'Pesanan dihapus.');

        return new RedirectResponse('/admin/member-orders', 303);
    }

    public function transition(Request $request, MemberOrder $memberOrder, StatusTransitionService $statuses)
    {
        abort_unless($request->user()?->can('access-admin'), 403);

        $memberOrder->loadMissing(['overrideStatus', 'paymentStatus']);
        if ($memberOrder->is_refunded) {
            return back()->withErrors([
                'status_id' => 'Pesanan refund sudah selesai dan tidak dapat diberi status khusus maupun status lainnya.',
            ]);
        }

        $data = $request->validate([
            'status_id' => ['nullable', 'integer', 'exists:order_statuses,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (filled($data['status_id'] ?? null)) {
            $statuses->transition(
                $memberOrder,
                OrderStatus::findOrFail($data['status_id']),
                $request->user(),
                $data['note'] ?? 'Status khusus pesanan diperbarui.'
            );

            return back()->with('status', 'Status khusus pesanan diperbarui.');
        }

        if ($memberOrder->override_status_id) {
            $statuses->clearOverride($memberOrder, $request->user(), $data['note'] ?? 'Pesanan kembali mengikuti status batch.');
        }

        return back()->with('status', 'Pesanan sekarang mengikuti status batch.');
    }

    public function paymentProof(MemberOrder $memberOrder)
    {
        abort_unless($memberOrder->payment_proof_path && Storage::disk('local')->exists($memberOrder->payment_proof_path), 404);

        return Storage::disk('local')->response($memberOrder->payment_proof_path);
    }

    private function formData(MemberOrder $order): array
    {
        $order->loadMissing(['member', 'items', 'batch.currentStatus', 'overrideStatus', 'paymentStatus']);

        // Ambil member yang eligible untuk NEW order (LINE-connected + active)
        // Untuk edit, include member yang sedang dipakai meskipun legacy
        $eligibleMembers = Member::eligibleForNewOrder()->get();
        if ($order->exists && $order->member_id) {
            $currentMember = Member::find($order->member_id);
            if ($currentMember && ! $eligibleMembers->contains('id', $currentMember->id)) {
                $eligibleMembers = $eligibleMembers->push($currentMember);
            }
        }

        $batches = Batch::query()
            ->with('currentStatus')
            ->where(fn ($query) => $query
                ->where('is_archived', false)
                ->when($order->batch_id, fn ($query, $batchId) => $query->orWhere('id', $batchId)))
            ->orderByDesc('created_at')
            ->get();

        return [
            'order' => $order,
            'batches' => $batches,
            'eligibleMembers' => $eligibleMembers,
            'itemStatuses' => OrderStatus::activeFor('order_item')->get(),
            'paymentStatuses' => OrderStatus::query()
                ->where('scope', 'payment')
                ->when(! $order->exists, fn ($query) => $query->where('code', '!=', 'refund'))
                ->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->when($order->payment_status_id, fn ($query, $statusId) => $query->orWhere('id', $statusId)))
                ->orderBy('sequence')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function syncItems(MemberOrder $order, array $items, StatusTransitionService $statuses, $user): void
    {
        $keptIds = [];

        foreach ($items as $itemData) {
            $statusId = $itemData['override_status_id'] ?? null;
            unset($itemData['override_status_id']);

            $itemData['subtotal'] = isset($itemData['unit_price'])
                ? ((int) $itemData['quantity']) * (float) $itemData['unit_price']
                : null;

            $item = $order->items()->updateOrCreate(
                ['id' => $itemData['id'] ?? null],
                $itemData
            );

            $keptIds[] = $item->id;

            if ($statusId && (int) $statusId !== $item->override_status_id) {
                $statuses->transition($item, OrderStatus::findOrFail($statusId), $user, 'Override status item.');
            } elseif (! $statusId && $item->override_status_id) {
                $statuses->clearOverride($item, $user);
            }
        }

        $order->items()->whereNotIn('id', $keptIds)->delete();
    }

    private function generateOrderCode(MemberOrder $order): string
    {
        return 'ORD-'.now()->format('ym').'-'.str_pad((string) $order->getKey(), 6, '0', STR_PAD_LEFT);
    }
}
