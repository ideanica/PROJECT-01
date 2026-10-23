<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use App\Services\PaymentVerificationService;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderPaymentController extends Controller
{
    public function index(MemberOrder $memberOrder)
    {
        $memberOrder->load(['member', 'batch', 'paymentStatus', 'overrideStatus', 'payments.creator']);
        $incoming = (float) $memberOrder->payments()->where('status', 'verified')->whereIn('type', OrderPayment::INCOMING_PAYMENT_TYPES)->where('amount', '>', 0)->sum('amount');
        $refunded = (float) $memberOrder->payments()->where('status', 'verified')->where('type', 'refund')->sum('amount');

        return view('admin.orders.payments', ['order' => $memberOrder, 'refundableAmount' => max(0, $incoming - $refunded)]);
    }

    public function refund(Request $request, MemberOrder $memberOrder, StatusTransitionService $statuses)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $proofPath = $request->file('proof')->store('payment-proofs', 'local');

        try {
            DB::transaction(function () use ($memberOrder, $request, $data, $proofPath, $statuses): void {
                $memberOrder->batch()->lockForUpdate()->firstOrFail();
                $order = MemberOrder::query()->with(['overrideStatus', 'paymentStatus'])->lockForUpdate()->findOrFail($memberOrder->id);
                if ($order->is_refunded) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Pesanan ini sudah direfund.']);
                }
                if ($order->payments()->where('status', 'pending')->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Selesaikan pemeriksaan transaksi tertunda sebelum memproses refund.']);
                }

                $incoming = (float) $order->payments()->where('status', 'verified')->whereIn('type', OrderPayment::INCOMING_PAYMENT_TYPES)->where('amount', '>', 0)->sum('amount');
                $refunded = (float) $order->payments()->where('status', 'verified')->where('type', 'refund')->sum('amount');
                $refundableAmount = round(max(0, $incoming - $refunded), 2);
                if ($refundableAmount <= 0 || round((float) $data['amount'], 2) !== $refundableAmount) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Nominal refund harus sama dengan dana terverifikasi yang belum dikembalikan (Rp '.number_format($refundableAmount, 0, ',', '.').').']);
                }

                $refundStatus = OrderStatus::query()->where('scope', 'payment')->where('code', 'refund')->where('is_active', true)->firstOrFail();
                $refundedOrderStatus = OrderStatus::query()->where('scope', 'member_order')->where('code', 'refunded')->where('is_active', true)->firstOrFail();
                $order->forceFill(['payment_status_id' => $refundStatus->id])->save();
                $order->payments()->create([
                    'type' => 'refund', 'amount' => $refundableAmount, 'status' => 'verified',
                    'proof_path' => $proofPath, 'paid_at' => now(), 'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
                ]);
                $statuses->transition($order, $refundedOrderStatus, $request->user(), 'Pesanan ditutup setelah refund tercatat di payment ledger.');
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($proofPath);
            throw $exception;
        }

        return back()->with('status', 'Refund tercatat di payment ledger dan status pesanan telah diperbarui.');
    }

    public function store(Request $request, MemberOrder $memberOrder, PaymentVerificationService $payments)
    {
        $data = $this->validatePayment($request);
        $proofPath = $request->hasFile('proof')
            ? $request->file('proof')->store('payment-proofs', 'local')
            : null;

        try {
            DB::transaction(function () use ($memberOrder, $request, $data, $proofPath, $payments): void {
                $memberOrder->batch()->lockForUpdate()->firstOrFail();
                $memberOrder = MemberOrder::query()->lockForUpdate()->findOrFail($memberOrder->id);
                $schedule = $payments->assertScheduledOrderPayment($memberOrder, (float) $data['amount']);
                $memberOrder->payments()->create([
                    'type' => $schedule['type'],
                    'amount' => $schedule['amount'],
                    'status' => 'pending',
                    'proof_path' => $proofPath,
                    'paid_at' => now(),
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        return back()->with('status', 'Bukti pembayaran tersimpan dan menunggu verifikasi melalui menu Transaksi.');
    }

    public function update(Request $request, MemberOrder $memberOrder, OrderPayment $payment)
    {
        abort_unless($payment->member_order_id === $memberOrder->id, 404);

        abort(403, 'Transaksi tidak dapat diubah dari ledger. Lakukan verifikasi atau penolakan melalui menu Transaksi.');
    }

    public function destroy(MemberOrder $memberOrder, OrderPayment $payment)
    {
        abort_unless($payment->member_order_id === $memberOrder->id, 404);
        abort_if($payment->customer_submitted, 403, 'Riwayat bukti customer tidak dapat dihapus.');
        abort_if($payment->status === 'verified', 403, 'Transaksi terverifikasi tidak dapat dihapus dari ledger.');

        $proof = $payment->proof_path;
        DB::transaction(function () use ($memberOrder, $payment): void {
            $memberOrder->batch()->lockForUpdate()->firstOrFail();
            $memberOrder = MemberOrder::query()->lockForUpdate()->findOrFail($memberOrder->id);
            $payment->delete();
            $this->syncPaymentStatus($memberOrder);
        });

        if ($proof) {
            Storage::disk('local')->delete($proof);
        }

        return back()->with('status', 'Record pembayaran dihapus.');
    }

    public function proof(MemberOrder $memberOrder, OrderPayment $payment)
    {
        abort_unless($payment->member_order_id === $memberOrder->id, 404);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function validatePayment(Request $request): array
    {
        return $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
    }

    private function syncPaymentStatus(MemberOrder $order): void
    {
        app(PaymentVerificationService::class)->sync($order);
    }
}
