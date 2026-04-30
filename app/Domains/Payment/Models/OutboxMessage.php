<?php

declare(strict_types=1);

namespace App\Domains\Payment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Outbox Message - represents a message to be delivered to external systems.
 *
 * Implements the Outbox pattern for reliable event delivery.
 */
final class OutboxMessage extends Model
{
    use TenantScoped;

    protected $table = 'outbox_messages';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'event_type',
        'target_url',
        'payload',
        'idempotency_key',
        'status', // pending, delivered, failed
        'attempt_count',
        'deliver_after',
        'last_attempt_at',
        'delivered_at',
        'failed_at',
        'response_code',
        'response_body',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'json',
        'metadata' => 'json',
        'deliver_after' => 'datetime',
        'last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'attempt_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeReadyToDeliver($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('deliver_after')
                    ->orWhere('deliver_after', '<=', now());
            });
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function canRetry(): bool
    {
        return $this->status === 'failed' || ($this->status === 'pending' && $this->attempt_count < 5);
    }
}
