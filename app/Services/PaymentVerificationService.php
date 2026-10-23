<?php

namespace App\Services;

use App\Models\MemberOrder;
use App\Models\OrderPayment;
use App\Models\OrderStatus;
use Illuminate\Validation\ValidationException;

class PaymentVerificationService
{
    public function assertScheduledOrderPayment(MemberOrder $order, float $amount, ?OrderPayment $exclude = null): array
    {
        if ($order->payments()->where('status', 'pending')->where('type', '!=', 'ems')
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude->id))
            ->exists()) {
            throw ValidationException::withMessages([
                'amount' => 'Ada transaksi pesanan yang masih menunggu verifikasi. Proses transaksi tersebut sebelum mengirim bukti lain.',
            ]);
        }

        $verifiedPayments = $order->payments()
            ->where('status', 'verified')
            ->where('type', '!=', 'ems')
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude->id))
            ->get();
        $order->setRelation('payments', $verifiedPayments);

        $schedule = app(PaymentSchedule::class)->forOrder($order);
        if ($schedule['amount'] <= 0 || round($amount, 2) !== round((float) $schedule['amount'], 2)) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal harus sama dengan tagihan aktif yang ditentukan sistem.',
            ]);
        }

        return $schedule;
    }

    public function balance(MemberOrder $order, bool $ems): float
    {
        if ($order->is_refunded) {
            return 0;
        }

        return $ems
            ? ($order->has_outstanding_ems_tax ? $order->ems_tax_remaining_amount : 0)
            : ($order->has_outstanding_order_payment ? $order->remaining_amount : 0);
    }

    public function assertPayable(MemberOrder $order, bool $ems, float $amount): void
    {
        $balance = $this->balance($order, $ems);
        if ($amount <= 0 || $balance <= 0 || $amount > $balance || ($ems && $amount !== $balance)) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal tidak sesuai dengan sisa tagihan terbaru. EMS & Pajak harus dibayar penuh.',
            ]);
        }
    }

    public function sync(MemberOrder $order, bool $ems = false): void
    {
        if ($order->is_refunded) {
            return;
        }

        if ($ems) {
            $paid = (float) $order->payments()->where('type', 'ems')->where('status', 'verified')->sum('amount');
            if ($order->ems_tax_is_published && $paid >= (float) $order->ems_tax_amount) {
                $order->update(['ems_tax_status' => 'paid']);
            }

            return;
        }

        $order->unsetRelation('payments');
        $code = match ($order->ledger_payment_state) {
            'paid' => 'lunas',
            'partial' => 'menunggu-pelunasan',
            default => 'menunggu-dp',
        };
        $status = OrderStatus::query()->where('scope', 'payment')->where('code', $code)->where('is_active', true)->first();
        if ($status && $order->payment_status_id !== $status->id) {
            $order->update(['payment_status_id' => $status->id]);
        }
    }
}
