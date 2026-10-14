<x-layouts.app title="Dashboard Admin">
    @php
        $summaryCards = [
            ['label' => 'Total pesanan', 'value' => $ordersCount, 'note' => 'Semua pesanan tercatat', 'icon' => 'bag', 'tone' => ''],
            ['label' => 'Tagihan EMS', 'value' => $unpaidEmsBills, 'note' => 'Masih menunggu pembayaran', 'icon' => 'bill', 'tone' => 'is-warning'],
            ['label' => 'Batch aktif', 'value' => $activeBatches, 'note' => 'Sedang berjalan', 'icon' => 'layers', 'tone' => 'is-accent'],
            ['label' => 'Pesanan selesai', 'value' => $completedOrders, 'note' => 'Proses telah tuntas', 'icon' => 'check', 'tone' => 'is-success'],
        ];

        $totalForRatio = max($ordersCount, 1);
        $healthRows = [
            ['label' => 'Dalam proses', 'count' => $processingOrders, 'ratio' => min(100, round(($processingOrders / $totalForRatio) * 100)), 'tone' => ''],
            ['label' => 'Selesai', 'count' => $completedOrders, 'ratio' => min(100, round(($completedOrders / $totalForRatio) * 100)), 'tone' => 'is-completed'],
            ['label' => 'Perlu perhatian', 'count' => $problemOrders, 'ratio' => min(100, round(($problemOrders / $totalForRatio) * 100)), 'tone' => 'is-problem'],
        ];
        $healthMini = [
            ['label' => 'Proses', 'value' => $processingOrders, 'alert' => false],
            ['label' => 'Selesai', 'value' => $completedOrders, 'alert' => false],
            ['label' => 'Kendala', 'value' => $problemOrders, 'alert' => true],
        ];

        $trackableLabels = [
            \App\Models\MemberOrder::class => 'Pesanan',
            \App\Models\Batch::class => 'Batch',
            \App\Models\OrderItem::class => 'Item',
        ];
    @endphp

    <div class="admin-dashboard">
        <header class="admin-dash-hero">
            <div>
                <span class="admin-dash-kicker">Admin Studio · Ringkasan hari ini</span>
                <h1 class="admin-dash-title">Selamat datang, <span>{{ auth()->user()->name }}</span>.</h1>
                <p class="admin-dash-lede">Pantau batch, perjalanan pesanan, dan tagihan EMS OceanPaws dalam satu layar.</p>
            </div>
        </header>

        <section class="admin-dash-stats" aria-label="Aktivitas operasional">
            @foreach ($summaryCards as $card)
                <article class="admin-dash-stat">
                    <div class="admin-dash-stat-head">
                        <span class="admin-dash-stat-icon{{ $card['tone'] ? ' ' . $card['tone'] : '' }}" aria-hidden="true">
                            @switch($card['icon'])
                                @case('bill')
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/></svg>
                                @break
                                @case('layers')
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/></svg>
                                @break
                                @case('check')
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
                                @break
                                @default
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                            @endswitch
                        </span>
                        <span class="admin-dash-live">Live</span>
                    </div>
                    <div class="admin-dash-stat-value">{{ number_format($card['value'], 0, ',', '.') }}</div>
                    <div class="admin-dash-stat-label">{{ $card['label'] }}</div>
                    <p class="admin-dash-stat-note">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <div class="admin-dash-grid">
            <section class="admin-dash-panel">
                <header class="admin-dash-titlebar">
                    <span class="admin-dash-titlebar-label">Kesehatan pesanan</span>
                    <span class="admin-dash-titlebar-action">{{ $ordersCount }} total</span>
                </header>
                <div class="admin-dash-panel-body">
                    <p class="admin-dash-section-hint">Komposisi progres berdasarkan seluruh pesanan.</p>
                    <div class="admin-dash-progress">
                        @foreach ($healthRows as $row)
                            <div>
                                <div class="admin-dash-progress-head">
                                    <span class="admin-dash-progress-label">{{ $row['label'] }}</span>
                                    <span class="admin-dash-progress-meta">{{ $row['count'] }} · {{ $row['ratio'] }}%</span>
                                </div>
                                <div class="admin-dash-bar">
                                    <div class="admin-dash-bar-fill {{ $row['tone'] }}" style="width: {{ $row['ratio'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="admin-dash-mini-grid">
                        @foreach ($healthMini as $mini)
                            @if ($mini['alert'])
                                <a class="admin-dash-mini is-alert is-link" href="{{ route('admin.member-orders.index', ['problem' => true]) }}">
                                    <div class="admin-dash-mini-value">{{ $mini['value'] }}</div>
                                    <div class="admin-dash-mini-label">{{ $mini['label'] }}</div>
                                </a>
                            @else
                                <div class="admin-dash-mini">
                                    <div class="admin-dash-mini-value">{{ $mini['value'] }}</div>
                                    <div class="admin-dash-mini-label">{{ $mini['label'] }}</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="admin-dash-panel">
                <header class="admin-dash-titlebar is-accent">
                    <span class="admin-dash-titlebar-label">Batch terbaru</span>
                    <a class="admin-dash-titlebar-action" href="{{ route('admin.batches.index') }}">
                        Lihat semua
                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </header>
                <div class="admin-dash-list">
                    @forelse ($latestBatches as $batch)
                        <a class="admin-dash-row" href="{{ route('admin.batches.show', $batch) }}">
                            <span class="admin-dash-row-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 3l9 4.5v9L12 21l-9-4.5v-9Z"/><path d="M3 7.5 12 12l9-4.5M12 12v9"/></svg>
                            </span>
                            <span class="admin-dash-row-copy">
                                <span class="admin-dash-row-code">{{ $batch->batch_number }}</span>
                                <span class="admin-dash-row-text">{{ $batch->batch_name ?: 'Tanpa nama batch' }}</span>
                            </span>
                            <span class="admin-dash-row-aside">
                                <x-status-badge :status="$batch->currentStatus" />
                                <svg viewBox="0 0 24 24" class="admin-dash-row-arrow h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </span>
                        </a>
                    @empty
                        <p class="admin-dash-empty">Belum ada batch.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="admin-dash-panel">
            <header class="admin-dash-titlebar">
                <span class="admin-dash-titlebar-label">Aktivitas status terbaru</span>
                <a class="admin-dash-titlebar-action" href="{{ route('admin.status-histories.index') }}">
                    Buka audit log
                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            </header>
            <div class="admin-dash-panel-body">
                <p class="admin-dash-section-hint">Perubahan status paling baru dari tim admin.</p>
                <div class="admin-dash-events">
                    @forelse ($latestHistories->chunk(4) as $historyGroup)
                        <div class="admin-dash-timeline">
                            @foreach ($historyGroup as $history)
                                @php
                                    $trackable = $history->trackable;
                                    $historyLabel = $trackableLabels[$history->trackable_type] ?? class_basename($history->trackable_type);
                                    $historyReference = match (true) {
                                        $trackable instanceof \App\Models\MemberOrder => $trackable->order_code,
                                        $trackable instanceof \App\Models\Batch => $trackable->batch_number,
                                        $trackable instanceof \App\Models\OrderItem => $trackable->item_name,
                                        default => null,
                                    };
                                @endphp
                                <article class="admin-dash-event">
                                    <span class="admin-dash-event-dot" aria-hidden="true"></span>
                                    <div>
                                        <div class="admin-dash-event-title">
                                            <span>{{ $historyLabel }}</span>
                                            @if ($historyReference)
                                                <span class="admin-dash-event-code">{{ $historyReference }}</span>
                                            @endif
                                            <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14m-5-5 5 5-5 5"/></svg>
                                            <x-status-badge :status="$history->newStatus" />
                                        </div>
                                        <p class="admin-dash-event-meta">{{ $history->changedBy?->name ?: 'Sistem' }} &middot; {{ $history->created_at->diffForHumans() }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @empty
                        <p class="admin-dash-empty">Belum ada riwayat.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
