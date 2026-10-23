<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\PaymentSetting;
use App\Services\PaymentSchedule;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['pending', 'verified', 'rejected'])],
            'type' => ['nullable', Rule::in(array_keys(OrderPayment::CUSTOMER_PAYMENT_LABELS))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);
        $query = OrderPayment::query()->with(['order.member', 'order.batch']);
        if ($search = trim($data['q'] ?? '')) {
            $query->whereHas('order', function ($orders) use ($search) {
                $orders->where('order_code', 'like', '%'.$search.'%')
                    ->orWhereHas('member', fn ($members) => $members->where('display_name', 'like', '%'.$search.'%')->orWhere('username', 'like', '%'.$search.'%'))
                    ->orWhereHas('batch', fn ($batches) => $batches->where('batch_number', 'like', '%'.$search.'%')->orWhere('batch_name', 'like', '%'.$search.'%'));
            });
        }
        foreach (['status', 'type'] as $filter) {
            if (! empty($data[$filter])) {
                $query->where($filter, $data[$filter]);
            }
        }
        if (! empty($data['date_from'])) {
            $query->whereDate('created_at', '>=', $data['date_from']);
        }
        if (! empty($data['date_to'])) {
            $query->whereDate('created_at', '<=', $data['date_to']);
        }

        return view('admin.transactions.index', [
            'payments' => $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest('created_at')->latest('id')->paginate(20)->withQueryString(),
            'summary' => OrderPayment::query()->selectRaw('status, COUNT(*) AS total, SUM(amount) AS amount')->groupBy('status')->get()->keyBy('status'),
            'dpPercentage' => app(PaymentSchedule::class)->percentage(),
        ]);
    }

    public function settings(Request $request, PaymentSchedule $schedule)
    {
        $data = $request->validate(['dp_percentage' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:99.99']]);
        DB::transaction(function () use ($data): void {
            PaymentSetting::query()->lockForUpdate()->findOrFail(1)->update($data);
        });
        $schedule->refresh();

        return back()->with('status', 'Persentase DP berhasil diperbarui. Tagihan yang sudah memiliki bukti tetap memakai persentase sebelumnya.');
    }

    public function show(OrderPayment $payment)
    {
        $payment->load(['order.member', 'order.batch', 'order.paymentStatus', 'order.overrideStatus', 'creator', 'reviewer']);

        return view('admin.transactions.show', compact('payment'));
    }

    public function review(Request $request, OrderPayment $payment, PaymentVerificationService $payments)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:verified,rejected'],
            'review_notes' => ['required_if:decision,rejected', 'nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $payment, $payments, $data): void {
            $payment->order->batch()->lockForUpdate()->firstOrFail();
            $order = MemberOrder::query()->lockForUpdate()->findOrFail($payment->member_order_id);
            $payment = OrderPayment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Transaksi ini sudah diproses. Muat ulang halaman untuk melihat status terbaru.']);
            }
            if ($data['decision'] === 'verified') {
                if (! $payment->proof_path || ! Storage::disk('local')->exists($payment->proof_path)) {
                    throw ValidationException::withMessages(['decision' => 'Bukti pembayaran tidak tersedia. Minta customer mengirim bukti yang valid.']);
                }
                if ($payment->type === 'ems') {
                    $payments->assertPayable($order, true, (float) $payment->amount);
                } else {
                    $payments->assertScheduledOrderPayment($order, (float) $payment->amount, $payment);
                }
            }
            $payment->update([
                'status' => $data['decision'], 'review_notes' => $data['review_notes'] ?? null,
                'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
            ]);
            if ($data['decision'] === 'verified') {
                $payments->sync($order, $payment->type === 'ems');
            }
        });

        return back()->with('status', $data['decision'] === 'verified' ? 'Pembayaran terverifikasi dan tagihan diperbarui.' : 'Bukti ditolak. Customer dapat mengirim bukti baru.');
    }
}
