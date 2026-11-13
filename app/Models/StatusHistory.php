<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusHistory extends Model
{
    public const UPDATED_AT = null;

    public const TRACKABLE_TYPES = [
        'batch' => ['label' => 'Batch', 'class' => Batch::class],
        'order' => ['label' => 'Pesanan', 'class' => MemberOrder::class],
        'item' => ['label' => 'Item Pesanan', 'class' => OrderItem::class],
    ];

    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'old_status_id',
        'new_status_id',
        'note',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function oldStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'old_status_id');
    }

    public function newStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'new_status_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function trackableLabel(): string
    {
        foreach (self::TRACKABLE_TYPES as $trackable) {
            if ($trackable['class'] === $this->trackable_type) {
                return $trackable['label'];
            }
        }

        return class_basename($this->trackable_type);
    }

    /** Cari riwayat berdasarkan referensi objek, catatan, status, atau admin yang mengubah. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = "%{$term}%";
        $matchingStatuses = fn () => OrderStatus::query()
            ->where('name', 'like', $like)
            ->orWhere('code', 'like', $like)
            ->select('id');

        return $query->where(function (Builder $query) use ($like, $matchingStatuses) {
            $query->where('note', 'like', $like)
                ->orWhereIn('old_status_id', $matchingStatuses())
                ->orWhereIn('new_status_id', $matchingStatuses())
                ->orWhereIn('changed_by', User::query()->where('name', 'like', $like)->select('id'))
                ->orWhere(fn (Builder $query) => $query->where('trackable_type', Batch::class)
                    ->whereIn('trackable_id', Batch::query()->where('batch_number', 'like', $like)->orWhere('batch_name', 'like', $like)->select('id')))
                ->orWhere(fn (Builder $query) => $query->where('trackable_type', MemberOrder::class)
                    ->whereIn('trackable_id', MemberOrder::query()->where('order_code', 'like', $like)->select('id')))
                ->orWhere(fn (Builder $query) => $query->where('trackable_type', OrderItem::class)
                    ->whereIn('trackable_id', OrderItem::query()->where('item_name', 'like', $like)->orWhere('variant', 'like', $like)->select('id')));
        });
    }
}
