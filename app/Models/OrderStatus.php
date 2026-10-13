<?php

namespace App\Models;

use Database\Factories\OrderStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    /** @use HasFactory<OrderStatusFactory> */
    use HasFactory;

    public const TYPES = ['process', 'success', 'failed', 'cancelled'];

    public const SCOPES = ['batch', 'member_order', 'order_item', 'payment', 'all'];

    public const SCOPE_DEFINITIONS = [
        'batch' => [
            'label' => 'Batch Pembelian',
            'title' => 'Progress Batch Pembelian',
            'description' => 'Tahapan operasional pembelian kolektif dari pemesanan supplier hingga distribusi.',
            'applies_to' => 'Manajemen Batch → tambah, edit, dan detail batch',
            'application' => 'Menjadi progress utama batch. Seluruh pesanan tanpa status khusus otomatis mengikuti status batch ini.',
            'form_hint' => 'progress utama seluruh pesanan dalam satu batch',
        ],
        'member_order' => [
            'label' => 'Pesanan Pelanggan',
            'title' => 'Status Khusus Pesanan Pelanggan',
            'description' => 'Kondisi satu pesanan pelanggan yang berbeda dari progress batch.',
            'applies_to' => 'Detail Pesanan → Kelola Status Khusus',
            'application' => 'Hanya mengubah pesanan yang dipilih dan tidak memengaruhi batch maupun pesanan pelanggan lainnya.',
            'form_hint' => 'override khusus untuk satu pesanan pelanggan',
        ],
        'order_item' => [
            'label' => 'Item Pesanan',
            'title' => 'Status Khusus Item Pesanan',
            'description' => 'Kondisi khusus satu produk di dalam pesanan pelanggan.',
            'applies_to' => 'Edit Pesanan → Status Item',
            'application' => 'Dipakai untuk kondisi seperti item kurang atau rusak tanpa mengubah status produk lain dalam pesanan.',
            'form_hint' => 'override khusus untuk satu produk dalam pesanan',
        ],
        'payment' => [
            'label' => 'Pembayaran Pesanan',
            'title' => 'Status Pembayaran Pesanan',
            'description' => 'Tahap pembayaran pelanggan yang berjalan terpisah dari progress barang.',
            'applies_to' => 'Form Tambah/Edit Pesanan → Status Pembayaran',
            'application' => 'Ditampilkan pada form dan detail pesanan untuk mencatat DP, pelunasan, atau pengembalian dana.',
            'form_hint' => 'status transaksi pembayaran pelanggan',
        ],
        'all' => [
            'label' => 'Lintas Fitur',
            'title' => 'Status Umum Lintas Fitur',
            'description' => 'Status generik dengan arti yang sama pada batch, pesanan, dan item.',
            'applies_to' => 'Batch Pembelian, Status Khusus Pesanan, dan Status Item',
            'application' => 'Gunakan hanya untuk kondisi umum seperti Siap Distribusi, Selesai, atau Dibatalkan.',
            'form_hint' => 'dapat digunakan pada batch, pesanan, dan item',
        ],
    ];

    public static function scopeDefinitions(): array
    {
        return self::SCOPE_DEFINITIONS;
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'sequence',
        'status_type',
        'scope',
        'is_initial',
        'is_final',
        'is_active',
        'locks_order_editing',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
            'is_active' => 'boolean',
            'locks_order_editing' => 'boolean',
        ];
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'current_status_id');
    }

    public function memberOrders(): HasMany
    {
        return $this->hasMany(MemberOrder::class, 'override_status_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'override_status_id');
    }

    public function paymentMemberOrders(): HasMany
    {
        return $this->hasMany(MemberOrder::class, 'payment_status_id');
    }

    public function oldHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'old_status_id');
    }

    public function newHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class, 'new_status_id');
    }

    public function scopeActiveFor($query, string $targetScope)
    {
        return $query->where('is_active', true)
            ->whereIn('scope', $targetScope === 'payment' ? ['payment'] : [$targetScope, 'all'])
            ->orderBy('sequence')
            ->orderBy('name');
    }

    public function isUsableFor(string $targetScope): bool
    {
        return $this->is_active && in_array($this->scope, $targetScope === 'payment' ? ['payment'] : [$targetScope, 'all'], true);
    }

    public function usedCount(): int
    {
        return $this->batches()->count()
            + $this->memberOrders()->count()
            + $this->orderItems()->count()
            + $this->paymentMemberOrders()->count()
            + $this->oldHistories()->count()
            + $this->newHistories()->count();
    }
}
