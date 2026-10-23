<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    use HasFactory;

    public const CUSTOMER_PAYMENT_LABELS = [
        'dp' => 'DP Pesanan',
        'installment' => 'Cicilan Pesanan',
        'settlement' => 'Pelunasan Pesanan',
        'full_payment' => 'Pembayaran Penuh',
        'other' => 'Pembayaran Lainnya',
        'ems' => 'EMS & Pajak',
        'refund' => 'Pengembalian Dana',
    ];

    public const INCOMING_PAYMENT_TYPES = ['dp', 'installment', 'settlement', 'full_payment', 'other', 'ems'];

    protected $fillable = [
        'member_order_id',
        'type',
        'amount',
        'status',
        'proof_path',
        'paid_at',
        'notes',
        'created_by',
        'customer_submitted',
        'submission_key',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'customer_submitted' => 'boolean',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::CUSTOMER_PAYMENT_LABELS[$this->type] ?? 'Pembayaran';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MemberOrder::class, 'member_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => 'Menunggu verifikasi',
        };
    }
}
