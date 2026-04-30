<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Escrow Hold - holds funds in escrow until conditions are met.
 *
 * Used for marketplace scenarios where funds are held until order fulfillment.
 * Supports partial releases and automatic release on expiration.
 */
final class EscrowHold extends Model
{
    use TenantScoped;

    protected $table = 'escrow_holds';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'payment_intent_id',
        'payment_transaction_id',
        'wallet_id',
        'amount_kopecks',
        'currency',
        'status',
        'release_conditions',
        'auto_release_at',
        'released_amount_kopecks',
        'remaining_amount_kopecks',
        'release_reason',
        'released_at',
        'canceled_at',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'amount_kopecks' => 'integer',
        'released_amount_kopecks' => 'integer',
        'remaining_amount_kopecks' => 'integer',
        'release_conditions' => 'json',
        'metadata' => 'json',
        'auto_release_at' => 'datetime',
        'released_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->remaining_amount_kopecks)) {
                $model->remaining_amount_kopecks = $model->amount_kopecks;
            }
        });
    }

    /** @return BelongsTo<PaymentIntent, self> */
    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class, 'payment_intent_id');
    }

    /** @return BelongsTo<PaymentTransaction, self> */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentTransaction::class, 'payment_transaction_id');
    }

    /** @return BelongsTo<Wallet, self> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Wallet\Models\Wallet::class, 'wallet_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'held');
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeExpired($query)
    {
        return $query->where('auto_release_at', '<', now());
    }

    public function isFullyReleased(): bool
    {
        return $this->remaining_amount_kopecks === 0 || $this->status === 'released';
    }

    public function hasRemainingFunds(): bool
    {
        return $this->remaining_amount_kopecks > 0;
    }

    public function canRelease(int $amount): bool
    {
        return $this->status === 'held' && $amount <= $this->remaining_amount_kopecks;
    }
}
