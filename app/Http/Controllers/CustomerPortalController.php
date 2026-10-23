<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CustomerPortalController extends Controller
{
    public function billing(Request $request)
    {
        if (in_array($request->query('tab'), ['paid', 'unpaid', 'refund'], true)) {
            return $this->legacyBilling($request);
        }

        $tab = $request->query('tab') === 'history' ? 'history' : 'active';
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,oldest,amount_desc,amount_asc'],
        ]);
        $search = mb_strtolower(trim($filters['q'] ?? ''));

        $user = $request->user();
        $member = $user?->role === 'customer' ? $user->member : null;
        $orders = $member
            ? $member->orders()->with(['batch.paymentMethod', 'overrideStatus', 'paymentStatus', 'payments'])->latest()->get()
            : collect();

        $orders->each(function ($order) use ($member, $request): void {
            $order->setAttribute('billing_tracking_url', URL::temporarySignedRoute(
                'tracking.order', now()->addMinutes(30),
                ['memberCode' => $member->member_code, 'memberOrder' => $order, 'return_to' => $request->getRequestUri()]
            ));
        });
        $matchesOrder = fn ($order) => $search === '' || str_contains(mb_strtolower(implode(' ', [
            $order->order_code, $order->batch?->batch_number, $order->batch?->batch_name,
        ])), $search);

        $obligations = $orders->flatMap(fn ($order) => collect($order->outstanding_obligations)
            ->map(fn ($obligation) => ['order' => $order, ...$obligation]));
        $visibleObligations = $obligations->filter(fn ($bill) => $matchesOrder($bill['order']));

        // Amount sorting compares bills, since one order may have two different balances.
        $visibleObligations = match ($filters['sort'] ?? 'newest') {
            'amount_desc' => $visibleObligations->sortByDesc('amount'),
            'amount_asc' => $visibleObligations->sortBy('amount'),
            'oldest' => $visibleObligations->sortBy(fn ($bill) => $bill['order']->created_at),
            default => $visibleObligations->sortByDesc(fn ($bill) => $bill['order']->created_at),
        };

        // Only actual, positive verified customer payments belong in the ledger.
        // EMS paid flags cannot supply a payment transaction/date. Refunds are explicit outflows.
        $payments = $orders->flatMap(fn ($order) => $order->payments
            ->filter(fn ($payment) => $payment->status === 'verified' && (float) $payment->amount > 0
                && isset(OrderPayment::CUSTOMER_PAYMENT_LABELS[$payment->type]))
            ->map(fn ($payment) => $payment->setRelation('order', $order)));
        $visiblePayments = $payments->filter(fn ($payment) => $matchesOrder($payment->order)
            || str_contains(mb_strtolower($payment->type_label), $search))
            ->sortByDesc('id')
            ->sortByDesc(fn ($payment) => $payment->paid_at ?? $payment->created_at)
            ->values();

        return view('portal.billing-list', [
            'user' => $user,
            'member' => $member,
            'tab' => $tab,
            'obligations' => $visibleObligations->values(),
            'payments' => $visiblePayments,
            'outstandingCount' => $obligations->count(),
            'outstandingTotal' => $obligations->sum('amount'),
        ]);
    }

    public function legacyBilling(Request $request)
    {
        $parameters = $request->only(['q', 'sort']);

        return match ($request->query('tab')) {
            'paid', 'history' => redirect()->route('billing.index', ['tab' => 'history', ...$parameters]),
            'refund' => redirect()->route('orders.history', ['filter' => 'refund', ...$parameters]),
            default => redirect()->route('billing.index', $parameters),
        };
    }

    public function services()
    {
        return view('portal.overview', [
            'type' => 'services',
            'title' => 'Layanan Pesanan',
            'description' => 'Pilih layanan yang kamu butuhkan untuk mulai jajan atau memantau pesanan.',
            'items' => [
                [
                    'icon' => 'bag',
                    'title' => 'Katalog',
                    'description' => 'Lihat batch yang dipublikasikan admin, variasi produk, harga DP dan pelunasan.',
                    'note' => 'Informasi batch terbaru',
                    'url' => route('catalog.index'),
                ],
                [
                    'icon' => 'search',
                    'title' => 'Lacak Pesanan',
                    'description' => 'Cari status terkini menggunakan kode pesanan atau username.',
                    'note' => 'Data real-time',
                    'url' => route('tracking.index'),
                ],
                [
                    'icon' => 'history',
                    'title' => 'Riwayat Pesanan',
                    'description' => 'Lihat seluruh batch, item, pembayaran, dan pengiriman milikmu.',
                    'note' => 'Tersimpan di profil',
                    'url' => route('orders.history'),
                ],
            ],
        ]);
    }

    public function unpaid(Request $request)
    {
        return redirect()->route('billing.index', $request->only(['q', 'sort']));
    }

    public function catalog(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:all,open,closed'],
        ]);
        $status = $filters['status'] ?? 'all';
        $batches = Batch::query()->where('is_catalog_visible', true)->where('is_archived', false)
            ->with(['currentStatus', 'products' => fn ($query) => $query->where('products.is_active', true)->wherePivot('is_available', true)])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('batch_number', 'like', "%{$search}%")
                    ->orWhere('batch_name', 'like', "%{$search}%")
                    ->orWhereHas('products', fn ($products) => $products->where('products.is_active', true)->where('batch_product.is_available', true)->where(function ($products) use ($search) {
                        $products->where('products.name', 'like', "%{$search}%")
                            ->orWhere('products.variant', 'like', "%{$search}%");
                    }));
            }))
            ->when($status === 'open', fn ($query) => $query->where(fn ($query) => $query->whereNull('ordering_deadline')->orWhere('ordering_deadline', '>', now())))
            ->when($status === 'closed', fn ($query) => $query->whereNotNull('ordering_deadline')->where('ordering_deadline', '<=', now()))
            ->latest()->paginate(9)->withQueryString();

        return view('portal.catalog', [
            'batches' => $batches,
            'dpPercentage' => app(\App\Services\PaymentSchedule::class)->percentage(),
            'status' => $status,
        ]);
    }

    public function catalogShow(Batch $batch)
    {
        abort_unless($batch->is_catalog_visible && ! $batch->is_archived, 404);

        $batch->load([
            'currentStatus',
            'products' => fn ($query) => $query->where('products.is_active', true)->wherePivot('is_available', true),
        ]);

        return view('portal.catalog-show', [
            'batch' => $batch,
            'dpPercentage' => app(\App\Services\PaymentSchedule::class)->percentage(),
            'emsAllocationFrozen' => $batch->ems_tax_amount !== null && $batch->orders()
                ->whereHas('payments', fn ($payments) => $payments->where('type', 'ems')->whereIn('status', ['pending', 'verified']))
                ->exists(),
        ]);
    }

    public function shipping(Request $request)
    {
        return $this->orderList($request, 'shipping');
    }

    public function refunds(Request $request)
    {
        return $this->orderList($request, 'refund');
    }

    public function history(Request $request)
    {
        if ($request->query('filter') === 'unpaid') {
            return $this->unpaid($request);
        }

        $user = $request->user();
        $member = $user?->member;
        $orders = collect();

        if ($member) {
            $orders = $member->orders()
                ->with($this->orderRelations())
                ->latest()
                ->get();

            $orders->each(function ($order) use ($member, $request): void {
                $order->setAttribute('history_tracking_url', URL::temporarySignedRoute(
                    'tracking.order',
                    now()->addMinutes(30),
                    ['memberCode' => $member->member_code, 'memberOrder' => $order, 'return_to' => $request->getRequestUri()]
                ));
            });
        }

        $filter = $request->string('filter')->toString();
        $filter = in_array($filter, ['active', 'history', 'shipping', 'refund'], true)
            ? $filter
            : 'all';

        $filteredOrders = $filter === 'all'
            ? $orders
            : $orders->filter(fn ($order) => in_array($filter, $this->orderGroups($order), true))->values();

        return view('portal.order-history', [
            'user' => $user,
            'member' => $member,
            'orders' => $this->filterOrderList($request, $filteredOrders),
            'allOrderCount' => $orders->count(),
            'filter' => $filter,
        ]);
    }

    private function orderList(Request $request, string $scope)
    {
        $user = $request->user();
        $member = $user?->member;
        $orders = collect();

        if ($member) {
            $orders = $member->orders()
                ->with($this->orderRelations())
                ->latest()
                ->get()
                ->filter(fn ($order) => in_array($scope, $this->orderGroups($order), true))
                ->values();

            $orders->each(function ($order) use ($member, $request): void {
                $order->setAttribute('portal_tracking_url', URL::temporarySignedRoute(
                    'tracking.order',
                    now()->addMinutes(30),
                    ['memberCode' => $member->member_code, 'memberOrder' => $order, 'return_to' => $request->getRequestUri()]
                ));
            });
        }

        $page = match ($scope) {
            'shipping' => [
                'eyebrow' => 'PENGIRIMAN',
                'title' => 'Pesanan sedang dikirim',
                'description' => 'Pantau paket yang sedang dikirim dan buka rincian tracking setiap pesanan.',
                'icon' => 'truck',
                'emptyTitle' => 'Belum ada pesanan dalam pengiriman.',
                'emptyDescription' => 'Pesanan yang mulai dikirim akan muncul otomatis di halaman ini.',
            ],
            'refund' => [
                'eyebrow' => 'REFUND',
                'title' => 'Pesanan direfund',
                'description' => 'Daftar pesanan yang pembayaran atau proses pesanannya telah direfund.',
                'icon' => 'swap',
                'emptyTitle' => 'Tidak ada pesanan yang direfund.',
                'emptyDescription' => 'Pesanan refund akan tercatat dan tetap dapat dilihat di sini.',
            ],
        };

        return view('portal.order-list', [
            'user' => $user,
            'member' => $member,
            'orders' => $this->filterOrderList($request, $orders),
            'scope' => $scope,
            ...$page,
        ]);
    }

    private function orderGroups(MemberOrder $order): array
    {
        $trackingStatus = $order->tracking_status;

        return array_values(array_filter([
            ! $trackingStatus?->is_final && ! $order->is_refunded && $trackingStatus?->code !== 'dikirim-ke-customer' ? 'active' : null,
            ! $order->is_refunded && $trackingStatus?->code === 'selesai' ? 'history' : null,
            ! $order->is_refunded && $trackingStatus?->code === 'dikirim-ke-customer' ? 'shipping' : null,
            $order->is_refunded ? 'refund' : null,
        ]));
    }

    private function filterOrderList(Request $request, $orders)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,oldest,amount_desc,amount_asc'],
        ]);
        $search = mb_strtolower(trim($filters['q'] ?? ''));
        $orders = $orders->filter(function ($order) use ($search) {
            $searchable = collect([
                $order->order_code, $order->go_number,
                $order->batch->batch_number, $order->batch->batch_name,
                ...$order->items->pluck('item_name')->all(),
            ])->implode(' ');

            return $search === '' || str_contains(mb_strtolower($searchable), $search);
        });

        return match ($filters['sort'] ?? 'newest') {
            'oldest' => $orders->sortBy('created_at')->values(),
            'amount_desc' => $orders->sortByDesc('total_amount')->values(),
            'amount_asc' => $orders->sortBy('total_amount')->values(),
            default => $orders->sortByDesc('created_at')->values(),
        };
    }

    private function orderRelations(): array
    {
        return [
            'batch.currentStatus',
            'batch.paymentMethod',
            'overrideStatus',
            'paymentStatus',
            'payments',
            'items.overrideStatus',
            'items.shipmentGroup.currentStatus',
        ];
    }
}
