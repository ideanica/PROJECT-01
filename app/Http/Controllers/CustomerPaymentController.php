<?php

namespace App\Http\Controllers;

use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\User;
use App\Models\PaymentSetting;
use App\Services\PaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CustomerPaymentController extends Controller
{
    public function show(Request $request, MemberOrder $memberOrder, string $billType)
    {
        $this->authorizeOwner($request, $memberOrder);
        abort_unless(in_array($billType, ['order', 'ems'], true), 404);
        $memberOrder->load(['batch.paymentMethod', 'paymentStatus', 'overrideStatus', 'payments']);

        return view('portal.payment', ['order' => $memberOrder, 'billType' => $billType]);
    }

    public function store(Request $request, MemberOrder $memberOrder, PaymentVerificationService $payments)
    {
        $this->authorizeOwner($request, $memberOrder);
        $data = $request->validate([
            'bill_type' => ['required', 'in:order,ems'],
            'submission_key' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);

        $path = null;
        try {
            DB::transaction(function () use ($request, $memberOrder, $payments, $data, &$path): void {
                // Batch allocation and manual EMS updates take this lock first too.
                $memberOrder->batch()->lockForUpdate()->firstOrFail();
                $order = MemberOrder::query()->lockForUpdate()->findOrFail($memberOrder->id);
                $this->authorizeOwner($request, $order);
                $existing = OrderPayment::where('submission_key', $data['submission_key'])->first();
                if ($existing) {
                    abort_unless($existing->member_order_id === $order->id && $existing->created_by === $request->user()->id, 403);
                    return;
                }

                $ems = $data['bill_type'] === 'ems';
                $pending = $order->payments()->where('status', 'pending');
                $ems ? $pending->where('type', 'ems') : $pending->where('type', '!=', 'ems');
                if ($pending->exists()) {
                    throw ValidationException::withMessages(['proof' => 'Bukti sebelumnya masih menunggu verifikasi admin.']);
                }
                if (! $ems && $order->payment_type === 'dp' && $order->paid_amount <= 0 && $order->dp_percentage === null) {
                    $settings = PaymentSetting::query()->lockForUpdate()->findOrFail(1);
                    $order->forceFill(['dp_percentage' => $settings->dp_percentage])->save();
                }
                $scheduledAmount = $ems ? $order->ems_tax_remaining_amount : $order->next_payment['amount'];
                if ((float) $data['amount'] !== $scheduledAmount) {
                    throw ValidationException::withMessages(['amount' => 'Nominal tagihan berubah atau tidak sesuai. Muat ulang halaman dan bayar nominal yang ditentukan sistem.']);
                }
                $payments->assertPayable($order, $ems, (float) $data['amount']);
                $path = $request->file('proof')->store('payment-proofs', 'local');
                abort_unless($path, 503, 'Bukti belum berhasil disimpan. Silakan coba kembali.');
                $type = $ems ? 'ems' : $order->next_payment['type'];
                $order->payments()->create([
                    'type' => $type, 'status' => 'pending', 'amount' => $scheduledAmount,
                    'paid_at' => now(), 'proof_path' => $path,
                    'created_by' => $request->user()->id, 'customer_submitted' => true,
                    'submission_key' => $data['submission_key'],
                ]);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return redirect()->route('billing.payments.show', [$memberOrder, $data['bill_type']])->with('payment_form', $memberOrder->id.'-'.$data['bill_type'])
            ->with('payment_status', 'Bukti berhasil dikirim. Pembayaran menunggu verifikasi admin.');
    }

    public function proof(Request $request, MemberOrder $memberOrder, OrderPayment $payment)
    {
        $this->authorizeOwner($request, $memberOrder);
        abort_unless($payment->member_order_id === $memberOrder->id, 404);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function authorizeOwner(Request $request, MemberOrder $order): void
    {
        abort_unless($request->user()?->role === User::ROLE_CUSTOMER
            && $request->user()->member_id === $order->member_id, 403);
    }
}
