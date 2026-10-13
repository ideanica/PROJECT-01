<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberHistoryLookupRequest;
use App\Http\Requests\TrackingLookupRequest;
use App\Models\Member;
use App\Models\MemberOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class MemberTrackingController extends Controller
{
    public function home()
    {
        return view('tracking.index', [
            'testimonialSlides' => collect(),
        ]);
    }

    public function index()
    {
        return view('tracking.search');
    }

    public function smartLookup(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ], [
            'query.required' => 'Masukkan kode tracking atau username pelanggan.',
        ]);

        $rawQuery = trim($validated['query']);
        $orderCode = mb_strtoupper($rawQuery);

        // Coba sebagai kode tracking terlebih dahulu. Ini menghindari tebakan format
        // karena kode tracking dan username sama-sama berupa teks.
        $order = MemberOrder::query()
            ->where('order_code', $orderCode)
            ->whereHas('member', fn ($member) => $member->where('is_active', true))
            ->with([
                'member',
                'batch.currentStatus',
                'batch.statusHistories.oldStatus',
                'batch.statusHistories.newStatus',
                'overrideStatus',
                'paymentStatus',
                'items.overrideStatus',
                'statusHistories.oldStatus',
                'statusHistories.newStatus',
            ])
            ->first();

        if ($order) {
            return view('tracking.search', [
                'searchType' => 'tracking',
                'searchQuery' => $orderCode,
                'orderResult' => $order,
                'timeline' => $this->timelineFor($order),
            ]);
        }

        $username = mb_strtolower(ltrim($rawQuery, '@'));
        $member = Member::query()
            ->where('username', $username)
            ->where('is_active', true)
            ->with([
                'orders' => fn ($orders) => $orders->latest(),
                'orders.batch.currentStatus',
                'orders.overrideStatus',
                'orders.paymentStatus',
                'orders.items.overrideStatus',
            ])
            ->first();

        if ($member) {
            return view('tracking.search', [
                'searchType' => 'username',
                'searchQuery' => $username,
                'memberResult' => $member,
            ]);
        }

        return back()
            ->withErrors(['query' => 'Kode tracking atau username tidak ditemukan. Periksa kembali data dari admin.'])
            ->onlyInput('query');
    }

    public function lookup(TrackingLookupRequest $request)
    {
        $lookup = $request->validated('lookup');

        $order = MemberOrder::query()
            ->where('order_code', $lookup)
            ->whereHas('member', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $order) {
            return back()->withErrors(['lookup' => 'Kode pesanan tidak ditemukan. Periksa kembali kode dari admin.'])->onlyInput('lookup');
        }

        return new RedirectResponse(URL::temporarySignedRoute(
            'tracking.progress',
            now()->addMinutes(15),
            ['orderCode' => $order->order_code]
        ), 303);
    }

    public function progress(string $orderCode)
    {
        $order = MemberOrder::query()
            ->where('order_code', $orderCode)
            ->whereHas('member', fn ($query) => $query->where('is_active', true))
            ->with([
                'member',
                'batch.currentStatus',
                'batch.statusHistories.oldStatus',
                'batch.statusHistories.newStatus',
                'overrideStatus',
                'paymentStatus',
                'items.overrideStatus',
                'statusHistories.oldStatus',
                'statusHistories.newStatus',
            ])
            ->firstOrFail();

        return view('tracking.progress', [
            'order' => $order,
            'timeline' => $this->timelineFor($order),
        ]);
    }

    public function historyLookup(MemberHistoryLookupRequest $request)
    {
        $member = Member::query()
            ->where('username', $request->validated('username'))
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return back()
                ->withErrors(['username' => 'Username tidak ditemukan pada data pelanggan.'])
                ->onlyInput('username');
        }

        return new RedirectResponse(URL::temporarySignedRoute(
            'tracking.member',
            now()->addMinutes(15),
            ['memberCode' => $member->member_code]
        ), 303);
    }

    public function member(string $memberCode)
    {
        $member = Member::where('member_code', $memberCode)
            ->where('is_active', true)
            ->with(['orders' => fn ($query) => $query->latest(), 'orders.batch.currentStatus', 'orders.overrideStatus', 'orders.paymentStatus', 'orders.items'])
            ->firstOrFail();

        return view('tracking.member', compact('member'));
    }

    public function order(string $memberCode, MemberOrder $memberOrder)
    {
        $member = Member::where('member_code', $memberCode)->where('is_active', true)->firstOrFail();

        abort_unless($memberOrder->member_id === $member->id, 403);

        $memberOrder->load([
            'member',
            'batch.currentStatus',
            'batch.statusHistories.oldStatus',
            'batch.statusHistories.newStatus',
            'overrideStatus',
            'paymentStatus',
            'items.overrideStatus',
            'statusHistories.oldStatus',
            'statusHistories.newStatus',
        ]);

        return view('tracking.order', [
            'member' => $member,
            'order' => $memberOrder,
            'timeline' => $this->timelineFor($memberOrder),
        ]);
    }

    private function timelineFor(MemberOrder $order)
    {
        $refundHistory = $order->statusHistories
            ->first(fn ($history) => $history->newStatus?->code === 'refunded');

        $batchHistories = $order->batch->statusHistories;
        if ($refundHistory) {
            $batchHistories = $batchHistories
                ->filter(fn ($history) => $history->created_at->lt($refundHistory->created_at)
                    || ($history->created_at->equalTo($refundHistory->created_at) && $history->id < $refundHistory->id));
        }

        $timeline = $batchHistories
            ->concat($order->statusHistories)
            ->sortByDesc('created_at')
            ->values();

        if ($refundHistory) {
            $refundHistory->setRelation('newStatus', $order->tracking_status);
        }

        return $timeline;
    }
}
