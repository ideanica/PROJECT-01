<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderPaymentController extends Controller
{
    public function index(MemberOrder $memberOrder)
    {
        $memberOrder->load(['member', 'batch', 'paymentStatus', 'payments.creator']);

        return view('admin.orders.payments', ['order' => $memberOrder]);
    }

    public function store(Request $request, MemberOrder $memberOrder)
    {
        $data = $this->validatePayment($request);
        $proofPath = $request->hasFile('proof')
            ? $request->file('proof')->store('payment-proofs', 'local')
            : null;

        DB::transaction(function () use ($memberOrder, $request, $data, $proofPath): void {
            $memberOrder->payments()->create([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'status' => $data['status'],
                'proof_path' => $proofPath,
                'paid_at' => $data['paid_at'] ?? ($data['status'] === 'verified' ? now() : null),
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $this->syncPaymentStatus($memberOrder);
        });

        return back()->with('status', 'Pembayaran berhasil dicatat.');
    }

    public function update(Request $request, MemberOrder $memberOrder, OrderPayment $payment)
    {
        abort_unless($payment->member_order_id === $memberOrder->id, 404);

        $data = $this->validatePayment($request);
        $oldProof = $payment->proof_path;
        $newProof = $request->hasFile('proof')
            ? $request->file('proof')->store('payment-proofs', 'local')
            : null;

        DB::transaction(function () use ($memberOrder, $payment, $data, $newProof): void {
            $payment->update([
                'type' => $data['type'],
                'amount' => $data['amount'],
                'status' => $data['status'],
                'proof_path' => $newProof ?: $payment->proof_path,
                'paid_at' => $data['paid_at'] ?? ($data['status'] === 'verified' ? ($payment->paid_at ?: now()) : null),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncPaymentStatus($memberOrder);
        });

        if ($newProof && $oldProof) {
            Storage::disk('local')->delete($oldProof);
        }

        return back()->with('status', 'Pembayaran berhasil diperbarui.');
    }

    public function destroy(MemberOrder $memberOrder, OrderPayment $payment)
    {
        abort_unless($payment->member_order_id === $memberOrder->id, 404);

        $proof = $payment->proof_path;
        DB::transaction(function () use ($memberOrder, $payment): void {
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

        return Storage::disk('local')->response($payment->proof_path);
    }

    private function validatePayment(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['dp', 'installment', 'settlement', 'other'])],
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['required', Rule::in(['pending', 'verified', 'rejected'])],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
    }

    private function syncPaymentStatus(MemberOrder $order): void
    {
        if ($order->is_refunded) {
            return;
        }

        $order->unsetRelation('payments');
        $code = match ($order->ledger_payment_state) {
            'paid' => 'lunas',
            'partial' => 'menunggu-pelunasan',
            default => 'menunggu-dp',
        };

        $status = OrderStatus::query()
            ->where('scope', 'payment')
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($status && $order->payment_status_id !== $status->id) {
            $order->update(['payment_status_id' => $status->id]);
        }
    }
}
