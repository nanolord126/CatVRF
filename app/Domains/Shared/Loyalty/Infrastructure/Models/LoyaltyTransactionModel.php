<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Loyalty\Domain\Entities\LoyaltyTransaction;
use Modules\Loyalty\Domain\Enums\LoyaltyTransactionType;
use Modules\Loyalty\Domain\ValueObjects\Points;

final class LoyaltyTransactionModel extends Model
{
    protected $table = 'loyalty_transactions';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'loyalty_program_id',
        'guest_loyalty_profile_id',
        'uuid',
        'type',
        'source_type',
        'source_id',
        'points_change',
        'balance_before',
        'balance_after',
        'order_amount',
        'point_multiplier',
        'description',
        'notes',
        'expires_at',
        'is_expired',
        'expired_at',
        'loyalty_rule_id',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'points_change' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'order_amount' => 'decimal:2',
        'point_multiplier' => 'decimal:4',
        'expires_at' => 'datetime',
        'is_expired' => 'boolean',
        'expired_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgramModel::class, 'loyalty_program_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(GuestLoyaltyProfileModel::class, 'guest_loyalty_profile_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LoyaltyRuleModel::class, 'loyalty_rule_id');
    }

    public function toDomain(): LoyaltyTransaction
    {
        return new LoyaltyTransaction(
            id: (string) $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            loyaltyProgramId: (string) $this->loyalty_program_id,
            guestLoyaltyProfileId: (string) $this->guest_loyalty_profile_id,
            type: LoyaltyTransactionType::from($this->type),
            sourceType: $this->source_type,
            sourceId: $this->source_id,
            pointsChange: Points::fromFloat((float) $this->points_change),
            balanceBefore: Points::fromFloat((float) $this->balance_before),
            balanceAfter: Points::fromFloat((float) $this->balance_after),
            orderAmount: $this->order_amount !== null ? (float) $this->order_amount : null,
            pointMultiplier: (float) $this->point_multiplier,
            description: $this->description,
            notes: $this->notes,
            expiresAt: $this->expires_at ? \DateTimeImmutable::createFromMutable($this->expires_at) : null,
            isExpired: $this->is_expired,
            expiredAt: $this->expired_at ? \DateTimeImmutable::createFromMutable($this->expired_at) : null,
            loyaltyRuleId: $this->loyalty_rule_id ? (string) $this->loyalty_rule_id : null,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: \DateTimeImmutable::createFromMutable($this->created_at)
        );
    }

    public static function fromDomain(LoyaltyTransaction $transaction): self
    {
        return new self([
            'id' => $transaction->getId() !== '0' ? $transaction->getId() : null,
            'uuid' => $transaction->getUuid(),
            'tenant_id' => $transaction->getTenantId(),
            'business_group_id' => $transaction->getBusinessGroupId(),
            'loyalty_program_id' => $transaction->getLoyaltyProgramId(),
            'guest_loyalty_profile_id' => $transaction->getGuestLoyaltyProfileId(),
            'type' => $transaction->getType()->value,
            'source_type' => $transaction->getSourceType(),
            'source_id' => $transaction->getSourceId(),
            'points_change' => $transaction->getPointsChange()->getValue(),
            'balance_before' => $transaction->getBalanceBefore()->getValue(),
            'balance_after' => $transaction->getBalanceAfter()->getValue(),
            'order_amount' => $transaction->getOrderAmount(),
            'point_multiplier' => $transaction->getPointMultiplier(),
            'description' => $transaction->getDescription(),
            'notes' => $transaction->getNotes(),
            'expires_at' => $transaction->getExpiresAt() ? \DateTime::createFromImmutable($transaction->getExpiresAt()) : null,
            'is_expired' => $transaction->isExpired(),
            'expired_at' => $transaction->getExpiredAt() ? \DateTime::createFromImmutable($transaction->getExpiredAt()) : null,
            'loyalty_rule_id' => $transaction->getLoyaltyRuleId(),
            'metadata' => $transaction->getMetadata(),
            'correlation_id' => $transaction->getCorrelationId(),
        ]);
    }
}
