<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * Payment Intent - represents intent to charge before actual payment.
 *
 * Acts as a payment request that can be processed asynchronously.
 * Supports multi-step payment flows (authorization → capture).
 */
final class PaymentIntent extends Model
{
    use TenantScoped;

    protected $table = 'payment_intents';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'payable_type', // Order, Subscription, etc.
        'payable_id',
        'amount_kopecks',
        'currency',
        'status',
        'payment_method',
        'payment_method_provider',
        'provider',
        'provider_payment_intent_id',
        'capture_method', // automatic or manual
        'confirmation_url',
        'payment_url',
        'client_secret',
        'requires_action',
        'next_action',
        'description',
        'customer_email',
        'customer_phone',
        'return_url',
        'cancel_url',
        'metadata',
        'expires_at',
        'canceled_at',
        'processing_at',
        'succeeded_at',
        'correlation_id',
        'fraud_score',
        'fraud_decision',
    ];

    protected $casts = [
        'amount_kopecks' => 'integer',
        'capture_method' => 'boolean', // true = automatic, false = manual
        'requires_action' => 'boolean',
        'metadata' => 'json',
        'expires_at' => 'datetime',
        'canceled_at' => 'datetime',
        'processing_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'fraud_score' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /** @return MorphTo<Model> */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /** @return HasMany<PaymentTransaction> */
    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Models\PaymentTransaction::class, 'payment_intent_id');
    }

    /** @return HasMany<EscrowHold> */
    public function escrowHolds(): HasMany
    {
        return $this->hasMany(EscrowHold::class, 'payment_intent_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeRequiresAction($query)
    {
        return $query->where('requires_action', true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeCaptured(): bool
    {
        return in_array($this->status, ['processing', 'requires_action']) && ! $this->isExpired();
    }

    public function canBeCanceled(): bool
    {
        return in_array($this->status, ['pending', 'processing', 'requires_action']) && ! $this->isExpired();
    }
}
