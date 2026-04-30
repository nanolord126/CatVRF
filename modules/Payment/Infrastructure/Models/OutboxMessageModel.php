<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payment\Domain\Entities\OutboxMessage;

class OutboxMessageModel extends Model
{
    use HasFactory;

    protected $table = 'outbox_messages';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'event_type',
        'target_url',
        'payload',
        'idempotency_key',
        'status',
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
        'payload' => 'array',
        'deliver_after' => 'immutable_datetime',
        'last_attempt_at' => 'immutable_datetime',
        'delivered_at' => 'immutable_datetime',
        'failed_at' => 'immutable_datetime',
        'metadata' => 'array',
    ];

    public function toDomain(): OutboxMessage
    {
        return new OutboxMessage(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            eventType: $this->event_type,
            targetUrl: $this->target_url,
            payload: $this->payload,
            idempotencyKey: $this->idempotency_key,
            status: $this->status,
            attemptCount: $this->attempt_count,
            deliverAfter: $this->deliver_after,
            lastAttemptAt: $this->last_attempt_at,
            deliveredAt: $this->delivered_at,
            failedAt: $this->failed_at,
            responseCode: $this->response_code,
            responseBody: $this->response_body,
            correlationId: $this->correlation_id,
            metadata: $this->metadata,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }
}
