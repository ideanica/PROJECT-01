<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\MemberOrder;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\BatchEmsTaxService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function cancel(
        Request $request,
        MemberOrder $memberOrder,
        StatusTransitionService $statuses,
        BatchEmsTaxService $emsTaxes,
    ) {
        $this->authorizeCustomerOrder($request, $memberOrder);

        DB::transaction(function () use ($request, $memberOrder, $statuses, $emsTaxes): void {
            $batch = Batch::query()->lockForUpdate()->findOrFail($memberOrder->batch_id);
            $order = MemberOrder::query()
                ->with(['overrideStatus', 'paymentStatus'])
                ->lockForUpdate()
                ->findOrFail($memberOrder->id);
            $this->authorizeCustomerOrder($request, $order);

            if (! $order->can_be_cancelled) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan hanya dapat dibatalkan jika belum memiliki pembayaran atau bukti pembayaran.',
                ]);
            }

            $batchHasProcessedEms = $batch->orders()->whereHas('payments', fn ($query) => $query
                ->where('type', 'ems')->whereIn('status', ['pending', 'verified']))->exists();
            if ($batchHasProcessedEms) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan belum dapat dibatalkan karena alokasi EMS batch sedang diproses.',
                ]);
            }

            $cancelledStatus = OrderStatus::query()
                ->where('code', 'dibatalkan')
                ->where('is_active', true)
                ->firstOrFail();

            $statuses->transition(
                $order,
                $cancelledStatus,
                $request->user(),
                'Pesanan dibatalkan oleh customer sebelum pembayaran.',
            );
            $emsTaxes->sync($batch);
        });

        return back()->with('status', 'Pesanan berhasil dibatalkan.');
    }

    private function authorizeCustomerOrder(Request $request, MemberOrder $order): void
    {
        abort_unless($request->user()?->role === User::ROLE_CUSTOMER
            && $request->user()->member_id === $order->member_id, 403);
    }
}
