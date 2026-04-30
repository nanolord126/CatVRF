<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Loyalty\Domain\Enums\LoyaltyTransactionType;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class LoyaltyTransaction
{
    public function __construct(
        private string $id,
        private string $uuid,
        private int $tenantId,
        private ?int $businessGroupId,
        private string $loyaltyProgramId,
        private string $guestLoyaltyProfileId,
        private LoyaltyTransactionType $type,
        private ?string $sourceType,
        private ?int $sourceId,
        private Points $pointsChange,
        private Points $balanceBefore,
        private Points $balanceAfter,
        private ?float $orderAmount,
        private float $pointMultiplier,
        private ?string $description,
        private ?string $notes,
        private ?DateTimeImmutable $expiresAt,
        private bool $isExpired,
        private ?DateTimeImmutable $expiredAt,
        private ?string $loyaltyRuleId,
        private ?array $metadata,
        private ?string $correlationId,
        private DateTimeImmutable $createdAt
    ) {
        if ($this->orderAmount !== null && $this->orderAmount < 0) {
            throw new DomainException('Order amount cannot be negative');
        }

        if ($this->pointMultiplier < 0) {
            throw new DomainException('Point multiplier cannot be negative');
        }

        if ($this->isExpired && $this->expiredAt === null) {
            throw new DomainException('Expired at must be set when transaction is expired');
        }
    }

    public static function create(
        string $uuid,
        int $tenantId,
        ?int $businessGroupId,
        string $loyaltyProgramId,
        string $guestLoyaltyProfileId,
        LoyaltyTransactionType $type,
        Points $pointsChange,
        Points $balanceBefore,
        Points $balanceAfter,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?float $orderAmount = null,
        float $pointMultiplier = 1.0,
        ?string $description = null,
        ?string $notes = null,
        ?DateTimeImmutable $expiresAt = null,
        ?string $loyaltyRuleId = null,
        ?array $metadata = null,
        ?string $correlationId = null
    ): self {
        return new self(
            id: '0', // Will be set by repository
            uuid: $uuid,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            loyaltyProgramId: $loyaltyProgramId,
            guestLoyaltyProfileId: $guestLoyaltyProfileId,
            type: $type,
            sourceType: $sourceType,
            sourceId: $sourceId,
            pointsChange: $pointsChange,
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            orderAmount: $orderAmount,
            pointMultiplier: $pointMultiplier,
            description: $description,
            notes: $notes,
            expiresAt: $expiresAt,
            isExpired: false,
            expiredAt: null,
            loyaltyRuleId: $loyaltyRuleId,
            metadata: $metadata,
            correlationId: $correlationId,
            createdAt: new DateTimeImmutable()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getBusinessGroupId(): ?int
    {
        return $this->businessGroupId;
    }

    public function getLoyaltyProgramId(): string
    {
        return $this->loyaltyProgramId;
    }

    public function getGuestLoyaltyProfileId(): string
    {
        return $this->guestLoyaltyProfileId;
    }

    public function getType(): LoyaltyTransactionType
    {
        return $this->type;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function getPointsChange(): Points
    {
        return $this->pointsChange;
    }

    public function getBalanceBefore(): Points
    {
        return $this->balanceBefore;
    }

    public function getBalanceAfter(): Points
    {
        return $this->balanceAfter;
    }

    public function getOrderAmount(): ?float
    {
        return $this->orderAmount;
    }

    public function getPointMultiplier(): float
    {
        return $this->pointMultiplier;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->isExpired;
    }

    public function getExpiredAt(): ?DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function getLoyaltyRuleId(): ?string
    {
        return $this->loyaltyRuleId;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isEarning(): bool
    {
        return $this->type === LoyaltyTransactionType::EARNED || $this->type === LoyaltyTransactionType::BONUS;
    }

    public function isRedemption(): bool
    {
        return $this->type === LoyaltyTransactionType::REDEEMED;
    }

    public function checkExpiration(DateTimeImmutable $now = null): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $now = $now ?? new DateTimeImmutable();
        return $now > $this->expiresAt;
    }

    public function markAsExpired(DateTimeImmutable $expiredAt): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            businessGroupId: $this->businessGroupId,
            loyaltyProgramId: $this->loyaltyProgramId,
            guestLoyaltyProfileId: $this->guestLoyaltyProfileId,
            type: $this->type,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            pointsChange: $this->pointsChange,
            balanceBefore: $this->balanceBefore,
            balanceAfter: $this->balanceAfter,
            orderAmount: $this->orderAmount,
            pointMultiplier: $this->pointMultiplier,
            description: $this->description,
            notes: $this->notes,
            expiresAt: $this->expiresAt,
            isExpired: true,
            expiredAt: $expiredAt,
            loyaltyRuleId: $this->loyaltyRuleId,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt
        );
    }
}
