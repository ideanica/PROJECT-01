<?php

namespace App\Models;

use Database\Factories\MemberOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MemberOrder extends Model
{
    /** @use HasFactory<MemberOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_code',
        'member_id',
        'batch_id',
        'go_number',
        'override_status_id',
        'payment_status_id',
        'order_source',
        'payment_type',
        'payment_amount',
        'payment_proof_path',
        'payment_submitted_at',
        'total_amount',
        'ems_tax_amount',
        'ems_tax_status',
        'ems_tax_due_date',
        'ems_tax_notes',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'payment_submitted_at' => 'datetime',
            'ems_tax_amount' => 'decimal:2',
            'ems_tax_due_date' => 'date',
        ];
    }

    public function getEmsTaxStatusLabelAttribute(): string
    {
        return match ($this->ems_tax_status) {
            'unpaid' => 'Menunggu pembayaran',
            'paid' => 'Lunas',
            default => 'Belum ditagihkan',
        };
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return match ($this->payment_type) {
            'dp' => 'DP · Belum lunas',
            'full' => 'Lunas',
            default => 'Belum ditentukan',
        };
    }

    public function getHasEmsTaxBillAttribute(): bool
    {
        return $this->ems_tax_amount !== null && (float) $this->ems_tax_amount > 0;
    }

    public function getEmsTaxIsPublishedAttribute(): bool
    {
        return $this->has_ems_tax_bill && in_array($this->ems_tax_status, ['unpaid', 'paid'], true);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function overrideStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'override_status_id');
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'payment_status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'trackable')->latest();
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments
                ->where('status', 'verified')
                ->sum(fn (OrderPayment $payment) => (float) $payment->amount);
        }

        return (float) $this->payments()->where('status', 'verified')->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) ($this->total_amount ?? 0) - $this->paid_amount);
    }

    public function getLedgerPaymentStateAttribute(): string
    {
        $total = (float) ($this->total_amount ?? 0);
        $paid = $this->paid_amount;

        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($total > 0 && $paid >= $total) {
            return 'paid';
        }

        return 'partial';
    }

    public function getEffectiveStatusAttribute(): ?OrderStatus
    {
        return $this->overrideStatus ?: $this->batch?->effective_status;
    }

    public function getIsRefundedAttribute(): bool
    {
        return $this->overrideStatus?->code === 'refunded'
            || $this->paymentStatus?->code === 'refund';
    }

    public function getTrackingStatusAttribute(): ?OrderStatus
    {
        if (! $this->is_refunded) {
            return $this->effective_status;
        }

        if (! $this->relationLoaded('trackingStatus')) {
            $this->setRelation(
                'trackingStatus',
                OrderStatus::query()->where('code', 'selesai')->first() ?: $this->effective_status
            );
        }

        return $this->getRelation('trackingStatus');
    }
}
