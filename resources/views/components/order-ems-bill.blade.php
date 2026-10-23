@props(['order', 'compact' => false, 'showEmpty' => false, 'showStatusBadge' => true])

@if($order->ems_tax_is_published || $showEmpty)
    <aside class="order-ems-bill {{ $compact ? 'order-ems-bill-compact' : '' }} {{ $order->ems_tax_status === 'paid' ? 'is-paid' : '' }}">
        <div class="order-ems-bill-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h10v18l-2-1.5L12 21l-3-1.5L7 21V3Z"/><path d="M9.5 8h5M9.5 12h5M9.5 16H12"/></svg>
        </div>
        <div class="order-ems-bill-copy">
            <span>Tagihan Pajak & EMS</span>
            @if($order->has_ems_tax_bill)
                <strong>Rp {{ number_format($order->ems_tax_amount, fmod((float) $order->ems_tax_amount, 1) > 0 ? 2 : 0, ',', '.') }}</strong>
                <small>
                    {{ $order->ems_tax_status_label }}
                    @if($order->ems_tax_due_date) · Jatuh tempo {{ $order->ems_tax_due_date->format('d M Y') }} @endif
                </small>
                @if($order->ems_tax_notes && !$compact)<p>{{ $order->ems_tax_notes }}</p>@endif
            @else
                <strong>Belum diterbitkan</strong>
                <small>Pajak EMS belum ditentukan atau belum dialokasikan dari batch.</small>
            @endif
        </div>
        @if($showStatusBadge)
            <x-status-badge class="order-ems-bill-status"
                :label="$order->has_ems_tax_bill ? match($order->ems_tax_status) { 'paid' => 'Lunas', 'unpaid' => 'Perlu dibayar', default => 'Draft' } : 'Belum ada'"
                :color="match($order->ems_tax_status) { 'paid' => '#bbf7d0', 'unpaid' => '#fef08a', default => '#e4e4e7' }" />
        @endif
    </aside>
@endif
