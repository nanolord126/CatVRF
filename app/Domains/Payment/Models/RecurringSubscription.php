<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Recurring Subscription - manages subscription billing.
 *
 * Supports daily, weekly, monthly, yearly billing cycles.
 * Integrates with payment intents for automatic billing.
 */
final class RecurringSubscription extends Model
{
    use TenantScoped;

    protected $table = 'recurring_subscriptions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'product_id',
        'product_type',
        'amount_kopecks',
        'currency',
        'status',
        'interval',
        'interval_count',
        'current_period_start',
        'current_period_end',
        'trial_start',
        'trial_end',
        'cancel_at_period_end',
        'canceled_at',
        'ended_at',
        'payment_method_id',
        'provider',
        'provider_subscription_id',
        'last_payment_at',
        'next_payment_at',
        'failed_payment_count',
        'max_retries',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'amount_kopecks' => 'integer',
        'cancel_at_period_end' => 'boolean',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
        'canceled_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'next_payment_at' => 'datetime',
        'failed_payment_count' => 'integer',
        'max_retries' => 'integer',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /** @return BelongsTo<User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /** @return HasMany<PaymentIntent> */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class, 'subscription_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', 'trialing');
    }

    public function scopePastDue($query)
    {
        return $query->where('status', 'past_due');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    public function scopeIncomplete($query)
    {
        return $query->where('status', 'incomplete');
    }

    public function isInTrial(): bool
    {
        return $this->status === 'trialing' && $this->trial_end && $this->trial_end->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function willCancelAtPeriodEnd(): bool
    {
        return $this->cancel_at_period_end && $this->canceled_at !== null;
    }

    public function shouldBillNow(): bool
    {
        if (! $this->isActive() && ! $this->isInTrial()) {
            return false;
        }

        return $this->next_payment_at && $this->next_payment_at->isPast();
    }

    public function incrementFailedPayment(): void
    {
        $this->increment('failed_payment_count');
    }

    public function resetFailedPaymentCount(): void
    {
        $this->update(['failed_payment_count' => 0]);
    }

    public function hasExceededMaxRetries(): bool
    {
        return $this->failed_payment_count >= ($this->max_retries ?? 3);
    }
}
