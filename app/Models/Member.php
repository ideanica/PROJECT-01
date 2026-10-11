<?php

namespace App\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'member_code',
        'customer_group_id',
        'display_name',
        'username',
        'line_username',
        'line_user_id',
        'avatar_url',
        'email',
        'phone',
        'address',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MemberOrder::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function scopeEligibleForNewOrder(Builder $query): Builder
    {
        return $query->whereHas('user', fn ($user) => $user->where('role', 'customer')->whereNotNull('password_set_at')->where('registration_pending', false))
            ->where('is_active', true);
    }

    public function scopeLegacy(Builder $query): Builder
    {
        return $query->whereDoesntHave('user', fn ($user) => $user->where('role', 'customer')->whereNotNull('password_set_at')->where('registration_pending', false));
    }

    public function isEligibleForNewOrder(): bool
    {
        return $this->is_active && ! $this->user?->registration_pending && $this->user?->role === 'customer' && $this->user?->password_set_at !== null;
    }

    public function getEligibilityStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Akun nonaktif - Tidak dapat digunakan untuk order baru';
        }

        if (! $this->isEligibleForNewOrder()) {
            return 'Akun belum siap - Tidak dapat digunakan untuk order baru';
        }

        return 'Akun terdaftar — Eligible';
    }

    public function setUsernameAttribute(?string $value): void
    {
        $this->attributes['username'] = $value ? mb_strtolower(trim($value)) : null;
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value ? mb_strtolower(trim($value)) : null;
    }
}
