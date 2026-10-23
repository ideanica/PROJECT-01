<?php

namespace App\Http\Controllers;

use App\Models\MemberOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $member = $user?->role === 'customer' ? $user->member : null;
        $orders = collect();

        if ($member) {
            $orders = $member->orders()
                ->with([
                    'batch.currentStatus',
                    'overrideStatus',
                    'paymentStatus',
                    'payments',
                    'items.overrideStatus',
                ])
                ->latest()
                ->get();

            $orders->each(function ($order) use ($member): void {
                $order->setAttribute('profile_tracking_url', URL::temporarySignedRoute(
                    'tracking.order',
                    now()->addMinutes(30),
                    ['memberCode' => $member->member_code, 'memberOrder' => $order]
                ));
            });
        }

        $stats = [
            // Count obligations, not orders: an order balance and EMS bill count separately.
            'unpaid' => $orders->sum(fn ($order) => count($order->outstanding_obligations)),
            'ems' => $orders->filter(fn ($order) => $order->has_outstanding_ems_tax)->count(),
            'history' => $orders->filter(fn ($order) => $order->effective_status?->code === 'selesai')->count(),
            'shipping' => $orders->filter(fn ($order) => $order->effective_status?->code === 'dikirim-ke-customer')->count(),
            'refund' => $orders->filter(fn ($order) => $order->is_refunded)->count(),
        ];

        $summary = [
            'orders' => $orders->count(),
            'items' => $orders->sum(fn ($order) => $order->items->sum('quantity')),
            'order_value' => $orders->sum(fn ($order) => (float) ($order->total_amount
                ?? $order->items->sum('subtotal'))),
            'payment_submitted' => $orders->sum(fn ($order) => (float) ($order->payment_amount ?? 0)),
            'ems_outstanding' => $orders
                ->filter(fn ($order) => $order->has_outstanding_ems_tax)
                ->sum(fn ($order) => $order->ems_tax_remaining_amount),
        ];

        $calendarMonth = now()->startOfMonth();
        $requestedMonth = $request->query('month');

        if (is_string($requestedMonth)
            && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $requestedMonth, $parts)
            && (int) $parts[1] >= 1) {
            $calendarMonth = Carbon::create((int) $parts[1], (int) $parts[2], 1)->startOfDay();
        }

        $calendarOrders = $orders->filter(
            fn ($order) => $order->created_at->format('Y-m') === $calendarMonth->format('Y-m')
        );
        $calendarItems = $calendarOrders->flatMap(
            fn ($order) => $order->items->map(fn ($item) => [
                'name' => $item->item_name,
                'quantity' => $item->quantity,
                'date' => $order->created_at,
            ])
        );

        return view('profile.show', [
            'user' => $user,
            'member' => $member,
            'orders' => $orders,
            'stats' => $stats,
            'summary' => $summary,
            'calendarMonth' => $calendarMonth,
            'calendarItems' => $calendarItems,
            'calendarOrderDays' => $calendarOrders->pluck('created_at')->map(fn ($date) => $date->day)->unique()->all(),
        ]);
    }

    public function paymentProof(Request $request, MemberOrder $memberOrder)
    {
        abort_unless(
            $request->user()?->member_id
            && $memberOrder->member_id === $request->user()->member_id,
            403
        );

        abort_unless(
            $memberOrder->payment_proof_path
            && Storage::disk('local')->exists($memberOrder->payment_proof_path),
            404
        );

        return Storage::disk('local')->response($memberOrder->payment_proof_path);
    }
}
