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

        $order = MemberOrder::query()
            ->where('order_code', $orderCode)
            ->whereHas('member', fn ($member) => $member->where('is_active', true))
            ->with($this->trackingRelations(true))
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
                'orders.items.shipmentGroup.currentStatus',
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

        return new RedirectResponse(route('tracking.progress', ['orderCode' => $order->order_code]), 303);
    }

    public function progress(Request $request, string $orderCode)
    {
        $order = MemberOrder::query()
            ->where('order_code', $orderCode)
            ->whereHas('member', fn ($query) => $query->where('is_active', true))
            ->with($this->trackingRelations(true))
            ->firstOrFail();

        $member = $request->user()?->member;

        // The full journey belongs to the order detail in the customer's own
        // order list, so only the owner may open it.
        abort_unless($member && $order->member_id === $member->id, 403);

        return $this->orderDetailView($order, $member, route('tracking.index'));
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
            ->with([
                'orders' => fn ($query) => $query->latest(),
                'orders.batch.currentStatus',
                'orders.overrideStatus',
                'orders.paymentStatus',
                'orders.items',
                'orders.items.shipmentGroup.currentStatus',
            ])
            ->firstOrFail();

        return view('tracking.member', compact('member'));
    }

    public function order(Request $request, string $memberCode, MemberOrder $memberOrder)
    {
        $member = Member::where('member_code', $memberCode)->where('is_active', true)->firstOrFail();

        abort_unless($memberOrder->member_id === $member->id, 403);

        $memberOrder->load($this->trackingRelations(true));

        return $this->orderDetailView($memberOrder, $member, $this->orderBackUrl($request));
    }

    private function orderDetailView(MemberOrder $order, Member $member, string $backUrl)
    {
        return view('tracking.order', [
            'member' => $member,
            'order' => $order,
            'timeline' => $this->timelineFor($order),
            'backUrl' => $backUrl,
        ]);
    }

    private function orderBackUrl(Request $request): string
    {
        foreach ([$request->query('return_to'), $request->headers->get('referer')] as $candidate) {
            if (! is_string($candidate) || preg_match('/[\x00-\x20\\\\]/', $candidate)) {
                continue;
            }

            if (str_starts_with($candidate, $request->getSchemeAndHttpHost().'/')) {
                $candidate = substr($candidate, strlen($request->getSchemeAndHttpHost()));
            }

            if (! str_starts_with($candidate, '/') || str_starts_with($candidate, '//')) {
                continue;
            }

            $path = parse_url($candidate, PHP_URL_PATH);
            if (! is_string($path) || $path === $request->getPathInfo()
                || preg_match('#^/(history/[^/]+/orders/|tracking/order/)#', $path)) {
                continue;
            }

            return $candidate;
        }

        return $request->user()?->member ? route('orders.history') : route('tracking.index');
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

    private function trackingRelations(bool $withTimeline = false): array
    {
        $relations = [
            'member',
            'batch.currentStatus',
            'overrideStatus',
            'paymentStatus',
            'payments',
            'items.overrideStatus',
            'items.shipmentGroup.currentStatus',
        ];

        if ($withTimeline) {
            $relations = array_merge($relations, [
                'batch.statusHistories.oldStatus',
                'batch.statusHistories.newStatus',
                'statusHistories.oldStatus',
                'statusHistories.newStatus',
            ]);
        }

        return $relations;
    }
}
