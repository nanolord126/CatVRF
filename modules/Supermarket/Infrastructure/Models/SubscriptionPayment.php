<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $table = 'supermarket_subscription_payments';

    protected $fillable = [
        'subscription_id',
        'order_id',
        'amount',
        'status',
        'payment_method',
        'gateway_transaction_id',
        'attempts',
        'last_attempt_at',
        'next_attempt_at',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SupermarketOrder::class, 'order_id');
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNeedsRetry($query)
    {
        return $query->where('status', 'failed')
            ->where('attempts', '<', 3)
            ->where(function ($q) {
                $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            });
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function needsRetry(): bool
    {
        return $this->isFailed() && $this->attempts < 3;
    }
}
