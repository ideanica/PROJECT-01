<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShipmentGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'name',
        'method',
        'tracking_number',
        'shipment_code',
        'current_status_id',
        'details_url',
        'last_co_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_co_at' => 'date',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function currentStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'current_status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
