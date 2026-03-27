<?php

declare(strict_types=1);

namespace App\Domains\Education\Kids\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * KidsVoucher Model - Children's Gift Certificates or Loyalty Vouchers.
 * Layer: Models (1/9)
 */
final class KidsVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kids_vouchers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'store_id',
        'customer_id',
        'code', // string, unique
        'voucher_type', // b2c_gift, b2b_credit, loyalty_reward
        'face_value', // int, kopecks
        'current_balance', // int, kopecks
        'expires_at',
        'is_rechargeable',
        'metadata', // JSON: birthday_name, sender_id, recipient_id
        'status', // active, redeemed, expired, blocked
        'correlation_id',
    ];

    protected $casts = [
        'face_value' => 'integer',
        'current_balance' => 'integer',
        'expires_at' => 'datetime',
        'is_rechargeable' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Boot the model with tenant and correlation scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'system');
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            $model->status = $model->status ?? 'active';
            $model->code = $model->code ?? strtoupper(Str::random(10));
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Store relationship.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(KidsStore::class, 'store_id');
    }

    /**
     * Active vouchers for redemption.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('current_balance', '>', 0);
    }

    /**
     * Corporate credit line vouchers for B2B.
     */
    public function scopeB2B(Builder $query): Builder
    {
        return $query->where('voucher_type', 'b2b_credit');
    }

    /**
     * Gift vouchers for B2C.
     */
    public function scopeGifts(Builder $query): Builder
    {
        return $query->where('voucher_type', 'b2c_gift');
    }

    /**
     * Check valid expiry.
     */
    public function isValid(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture() && $this->current_balance > 0;
    }

    /**
     * Get sender greeting text.
     */
    public function getGreetingAttribute(): ?string
    {
        return $this->metadata['greeting'] ?? null;
    }

    /**
     * Formatted balance display helper.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->current_balance / 100, 2, '.', ' ') . ' RUB';
    }
}
