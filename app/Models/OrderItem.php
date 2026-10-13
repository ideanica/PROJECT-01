<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'member_order_id',
        'product_id',
        'item_name',
        'variant',
        'quantity',
        'unit_price',
        'subtotal',
        'override_status_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MemberOrder::class, 'member_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function overrideStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'override_status_id');
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'trackable')->latest();
    }

    public function getEffectiveStatusAttribute(): ?OrderStatus
    {
        $orderStatus = $this->order?->effective_status;

        if ($orderStatus?->code === 'refunded') {
            return $orderStatus;
        }

        return $this->overrideStatus ?: $orderStatus;
    }
}
