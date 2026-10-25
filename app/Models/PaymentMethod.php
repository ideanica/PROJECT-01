<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'image_path',
        'account_name',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the active payment method for a given type.
     */
    public static function getActiveForType(string $type): ?self
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Get the active QRIS payment method.
     */
    public static function getActiveQris(): ?self
    {
        return static::getActiveForType('qris');
    }

    /**
     * Get the image URL for the payment method.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'https://') || str_starts_with($this->image_path, 'http://')) {
            return $this->image_path;
        }

        if (str_starts_with($this->image_path, 'assets/')) {
            return asset($this->image_path);
        }

        return '/storage/'.ltrim($this->image_path, '/');
    }
}
