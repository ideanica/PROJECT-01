<?php

namespace App\Services;

use App\Models\MemberOrder;
use App\Models\PaymentSetting;

class PaymentSchedule
{
    private ?float $percentage = null;

    public function percentage(): float
    {
        return $this->percentage ??= (float) PaymentSetting::query()->findOrFail(1)->dp_percentage;
    }

    public function refresh(): void
    {
        $this->percentage = null;
    }

    public function forOrder(MemberOrder $order): array
    {
        $percentage = (float) ($order->dp_percentage ?? $this->percentage());
        $total = (float) $order->total_amount;
        $paid = $order->paid_amount;
        $remaining = $order->remaining_amount;
        $dp = min($total, max(0.01, round($total * $percentage / 100, 2)));
        $isFirstDp = $order->payment_type === 'dp' && $paid <= 0;

        return [
            'percentage' => $percentage,
            'total' => $total,
            'dp' => $dp,
            'paid' => $paid,
            'remaining' => $remaining,
            'amount' => $order->has_outstanding_order_payment ? ($isFirstDp ? min($remaining, $dp) : $remaining) : 0,
            'type' => $isFirstDp ? 'dp' : ($paid > 0 ? 'settlement' : 'full_payment'),
            'label' => $isFirstDp ? 'DP' : ($paid > 0 ? 'Pelunasan' : 'Pembayaran pesanan'),
        ];
    }
}
