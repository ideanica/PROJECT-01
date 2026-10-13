<?php

namespace App\Http\Controllers;

use App\Models\MemberOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CustomerPortalController extends Controller
{
    public function orders(Request $request)
    {
        $user = $request->user();
        $member = $user?->member;

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $member) {
            return redirect()->route('admin.dashboard');
        }

        $orders = $member->orders()->with(['batch', 'paymentStatus'])->latest()->get();
        $unpaidCount = $orders
            ->filter(fn ($order) => in_array($order->paymentStatus?->code, ['menunggu-dp', 'menunggu-pelunasan'], true))
            ->count();
        $refundCount = $orders->filter(fn ($order) => $order->is_refunded)->count();

        return view('portal.overview', [
            'type' => 'orders',
            'title' => 'Pusat Pesanan',
            'description' => 'Semua riwayat pesanan dan pembayaranmu tersusun dalam satu tempat.',
            'items' => [
                [
                    'icon' => 'bag',
                    'title' => 'Pesananku',
                    'description' => 'Lihat seluruh riwayat, status proses, dan perjalanan paket pesananmu.',
                    'note' => $orders->count().' pesanan tersimpan',
                    'url' => route('orders.history'),
                ],
                [
                    'icon' => 'card',
                    'title' => 'Tagihan',
                    'description' => 'Periksa transaksi yang belum dibayar, berhasil, maupun direfund.',
                    'note' => $unpaidCount.' perlu dibayar · '.$refundCount.' refund',
                    'url' => route('billing.orders'),
                ],
            ],
        ]);
    }

    public function billing(Request $request)
    {
        $member = $request->user()?->member;
        $orders = $member
            ? $member->orders()->with(['batch', 'paymentStatus'])->latest()->get()
            : collect();

        $unpaidCount = $orders
            ->filter(fn ($order) => in_array($order->paymentStatus?->code, ['menunggu-dp', 'menunggu-pelunasan'], true))
            ->count();
        $emsCount = $orders->where('ems_tax_status', 'unpaid')->count();

        return view('portal.overview', [
            'type' => 'billing',
            'title' => 'Tagihan & Pembayaran',
            'description' => 'Pantau tagihan pesanan, Pajak/EMS, dan riwayat pembayaranmu dari satu tempat.',
            'items' => [
                [
                    'icon' => 'card',
                    'title' => 'Tagihan Pesanan',
                    'description' => 'Lihat DP atau pelunasan batch yang masih menunggu pembayaran.',
                    'note' => $member ? $unpaidCount.' belum dibayar' : 'Login untuk melihat',
                    'url' => route('billing.orders'),
                ],
                [
                    'icon' => 'document',
                    'title' => 'Tagihan EMS & Pajak',
                    'description' => 'Periksa ongkir EMS dan pajak impor beserta rincian tiap pesanan.',
                    'note' => $member ? $emsCount.' tagihan aktif' : 'Rincian transparan',
                    'url' => route('billing.ems'),
                ],
            ],
        ]);
    }

    public function billingOrders(Request $request)
    {
        return $this->billingList($request, 'orders');
    }

    public function billingEms(Request $request)
    {
        return $this->billingList($request, 'ems');
    }

    public function services()
    {
        return view('portal.overview', [
            'type' => 'services',
            'title' => 'Layanan Pesanan',
            'description' => 'Pilih layanan yang kamu butuhkan untuk mulai jajan atau memantau pesanan.',
            'items' => [
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
        return $this->orderList($request, 'unpaid');
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
        $user = $request->user();
        $member = $user?->member;
        $orders = collect();

        if ($member) {
            $orders = $member->orders()
                ->with([
                    'batch.currentStatus',
                    'overrideStatus',
                    'paymentStatus',
                    'items.overrideStatus',
                ])
                ->latest()
                ->get();

            $orders->each(function ($order) use ($member): void {
                $order->setAttribute('history_tracking_url', URL::temporarySignedRoute(
                    'tracking.order',
                    now()->addMinutes(30),
                    ['memberCode' => $member->member_code, 'memberOrder' => $order]
                ));
            });
        }

        $filter = $request->string('filter')->toString();
        $filter = in_array($filter, ['unpaid', 'active', 'history', 'shipping', 'refund'], true)
            ? $filter
            : 'all';

        $filteredOrders = $filter === 'all'
            ? $orders
            : $orders->filter(fn ($order) => in_array($filter, $this->orderGroups($order), true))->values();

        return view('portal.order-history', [
            'user' => $user,
            'member' => $member,
            'orders' => $filteredOrders,
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
                ->with([
                    'batch.currentStatus',
                    'overrideStatus',
                    'paymentStatus',
                    'items.overrideStatus',
                ])
                ->latest()
                ->get()
                ->filter(fn ($order) => in_array($scope, $this->orderGroups($order), true))
                ->values();

            $orders->each(function ($order) use ($member): void {
                $order->setAttribute('portal_tracking_url', URL::temporarySignedRoute(
                    'tracking.order',
                    now()->addMinutes(30),
                    ['memberCode' => $member->member_code, 'memberOrder' => $order]
                ));
            });
        }

        $page = match ($scope) {
            'unpaid' => [
                'eyebrow' => 'PEMBAYARAN PESANAN',
                'title' => 'Pesanan belum dibayar',
                'description' => 'Daftar pesanan dengan DP atau pelunasan yang masih perlu diselesaikan.',
                'icon' => 'wallet',
                'emptyTitle' => 'Tidak ada pesanan yang belum dibayar.',
                'emptyDescription' => 'Semua pembayaran pesananmu sudah aman untuk saat ini.',
            ],
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
            'orders' => $orders,
            'scope' => $scope,
            ...$page,
        ]);
    }

    private function orderGroups(MemberOrder $order): array
    {
        $trackingStatus = $order->tracking_status;

        return array_values(array_filter([
            in_array($order->paymentStatus?->code, ['menunggu-dp', 'menunggu-pelunasan'], true) ? 'unpaid' : null,
            ! $trackingStatus?->is_final && ! $order->is_refunded ? 'active' : null,
            $trackingStatus?->code === 'selesai' ? 'history' : null,
            $trackingStatus?->code === 'dikirim-ke-customer' ? 'shipping' : null,
            $order->is_refunded ? 'refund' : null,
        ]));
    }

    private function billingList(Request $request, string $scope)
    {
        $user = $request->user();
        $member = $user?->member;
        $orders = collect();

        if ($member) {
            $orders = $member->orders()
                ->with([
                    'batch.currentStatus',
                    'overrideStatus',
                    'paymentStatus',
                    'items',
                ])
                ->latest()
                ->get();

            $orders->each(function ($order) use ($member): void {
                $order->setAttribute('billing_tracking_url', URL::temporarySignedRoute(
                    'tracking.order',
                    now()->addMinutes(30),
                    ['memberCode' => $member->member_code, 'memberOrder' => $order]
                ));
            });
        }

        $requestedTab = $request->string('tab')->toString();
        $tab = in_array($requestedTab, ['unpaid', 'paid', 'refund'], true) ? $requestedTab : 'unpaid';

        if ($scope === 'ems') {
            $unpaidOrders = $orders->filter(fn ($order) => $order->ems_tax_is_published && $order->ems_tax_status === 'unpaid')->values();
            $paidOrders = $orders->filter(fn ($order) => $order->ems_tax_is_published && $order->ems_tax_status === 'paid')->values();
            $refundOrders = collect();
            $title = 'Tagihan EMS & Pajak';
            $description = 'Rincian ongkir internasional dan pajak impor untuk setiap pesananmu.';
        } else {
            $unpaidOrders = $orders
                ->filter(fn ($order) => ! $order->is_refunded && in_array($order->paymentStatus?->code, ['menunggu-dp', 'menunggu-pelunasan'], true))
                ->values();
            $paidOrders = $orders
                ->filter(fn ($order) => ! $order->is_refunded && ($order->paymentStatus?->code === 'lunas' || $order->payment_submitted_at !== null))
                ->values();
            $refundOrders = $orders->filter(fn ($order) => $order->is_refunded)->values();
            $title = 'Tagihan';
            $description = 'Semua transaksi yang belum dibayar, berhasil, dan direfund tersusun di sini.';
        }

        $visibleOrders = match ($tab) {
            'paid' => $paidOrders,
            'refund' => $refundOrders,
            default => $unpaidOrders,
        };

        return view('portal.billing-list', [
            'user' => $user,
            'member' => $member,
            'scope' => $scope,
            'tab' => $tab,
            'title' => $title,
            'description' => $description,
            'orders' => $visibleOrders,
            'unpaidCount' => $unpaidOrders->count(),
            'paidCount' => $paidOrders->count(),
            'refundCount' => $refundOrders->count(),
        ]);
    }
}
