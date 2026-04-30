<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Payment\Domain\Entities\EscrowHold;
use Modules\Payment\Domain\ValueObjects\Money;

class EscrowHoldModel extends Model
{
    use HasFactory;

    protected $table = 'escrow_holds';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'payment_intent_id',
        'wallet_id',
        'amount_kopecks',
        'currency',
        'status',
        'release_conditions',
        'auto_release_at',
        'remaining_amount_kopecks',
        'released_amount_kopecks',
        'release_reason',
        'released_at',
        'canceled_at',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'release_conditions' => 'array',
        'auto_release_at' => 'immutable_datetime',
        'released_at' => 'immutable_datetime',
        'canceled_at' => 'immutable_datetime',
        'metadata' => 'array',
    ];

    public function toDomain(): EscrowHold
    {
        return new EscrowHold(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            paymentIntentId: $this->payment_intent_id,
            walletId: $this->wallet_id,
            amount: new Money($this->amount_kopecks, $this->currency),
            currency: $this->currency,
            status: $this->status,
            releaseConditions: $this->release_conditions,
            autoReleaseAt: $this->auto_release_at,
            remainingAmount: new Money($this->remaining_amount_kopecks, $this->currency),
            releasedAmount: new Money($this->released_amount_kopecks, $this->currency),
            releaseReason: $this->release_reason,
            releasedAt: $this->released_at,
            canceledAt: $this->canceled_at,
            correlationId: $this->correlation_id,
            metadata: $this->metadata,
            createdAt: $this->created_at->toImmutable(),
            updatedAt: $this->updated_at->toImmutable(),
        );
    }
}
