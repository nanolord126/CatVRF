<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Refund Transaction - tracks refund operations.
 *
 * Supports partial refunds, multiple refunds per payment, and refund tracking.
 */
final class RefundTransaction extends Model
{
    use TenantScoped;

    protected $table = 'refund_transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'payment_transaction_id',
        'payment_intent_id',
        'wallet_id',
        'amount_kopecks',
        'currency',
        'status',
        'reason',
        'reason_code',
        'provider',
        'provider_refund_id',
        'refund_method',
        'processing_fee_kopecks',
        'refunded_amount_kopecks',
        'requested_by',
        'requested_by_type', // user, admin, system
        'approved_by',
        'approved_at',
        'processed_at',
        'failed_at',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'amount_kopecks' => 'integer',
        'processing_fee_kopecks' => 'integer',
        'refunded_amount_kopecks' => 'integer',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
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

    /** @return BelongsTo<PaymentTransaction, self> */
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PaymentTransaction::class, 'payment_transaction_id');
    }

    /** @return BelongsTo<PaymentIntent, self> */
    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class, 'payment_intent_id');
    }

    /** @return BelongsTo<Wallet, self> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Wallet\Models\Wallet::class, 'wallet_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isPartial(): bool
    {
        return $this->amount_kopecks !== $this->paymentTransaction->amount;
    }

    public function netRefundAmount(): int
    {
        return $this->refunded_amount_kopecks - ($this->processing_fee_kopecks ?? 0);
    }
}
