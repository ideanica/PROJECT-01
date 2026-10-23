@props(['order', 'billType' => 'order'])

@if($billType === 'ems' ? $order->has_outstanding_ems_tax : $order->has_outstanding_order_payment)
    <a class="order-payment-method order-payment-trigger" href="{{ route('billing.payments.show', [$order, $billType]) }}">
        <span class="order-payment-method-icon"><x-public-icon variant="rounded-bold" name="wallet" :size="20" /></span><span>Bayar Sekarang</span><x-public-icon name="chevron-right" :size="18" />
    </a>
@endif
