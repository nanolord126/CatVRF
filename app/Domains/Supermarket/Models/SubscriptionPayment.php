<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubscriptionPayment extends Model
{
    use HasFactory;

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
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SupermarketOrder::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeNeedsRetry($query)
    {
        return $query->where('status', 'failed')
            ->where('attempts', '<', 3)
            ->where('next_attempt_at', '<=', now());
    }

    public function isRetryable(): bool
    {
        return $this->status === 'failed' && $this->attempts < 3;
    }

    public function isFinalFailure(): bool
    {
        return $this->status === 'failed' && $this->attempts >= 3;
    }

    public function markAsAttempted(string $error = null): void
    {
        $this->increment('attempts');
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'last_attempt_at' => now(),
            'next_attempt_at' => now()->addHours($this->calculateNextAttemptDelay($this->attempts))
        ]);
    }

    public function markAsSuccess(string $transactionId): void
    {
        $this->update([
            'status' => 'success',
            'gateway_transaction_id' => $transactionId,
            'last_attempt_at' => now(),
        ]);
    }

    private function calculateNextAttemptDelay(int $attempt): int
    {
        return match($attempt) {
            1 => 4,
            2 => 24,
            default => 48
        };
    }
}
