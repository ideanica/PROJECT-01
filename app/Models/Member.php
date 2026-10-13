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

    /** Member aktif boleh dicatat ke order, terlepas dari kesiapan akun login. */
    public function scopeAvailableForOrder(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isAvailableForOrder(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope legacy lama tetap dipertahankan untuk halaman administrasi akun.
     * Jangan gunakan scope ini untuk menentukan apakah buyer boleh memiliki order.
     */
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

    public function getAccountStateAttribute(): string
    {
        if (! $this->user) {
            return 'unlinked';
        }

        return $this->user->registration_pending ? 'pending_claim' : 'linked';
    }

    public function getEligibilityStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Member nonaktif';
        }

        return match ($this->account_state) {
            'unlinked' => 'Belum punya akun login',
            'pending_claim' => 'Claim akun menunggu persetujuan',
            default => 'Akun terhubung',
        };
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
