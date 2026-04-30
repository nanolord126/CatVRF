<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Loyalty\Domain\Enums\LoyaltyRewardType;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class LoyaltyReward
{
    public function __construct(
        private string $id,
        private string $uuid,
        private int $tenantId,
        private string $loyaltyProgramId,
        private string $name,
        private ?string $description,
        private LoyaltyRewardType $type,
        private Points $pointsCost,
        private string $valueType,
        private ?float $valueAmount,
        private ?int $menuItemId,
        private ?string $itemCode,
        private ?array $conditions,
        private ?int $stockQuantity,
        private int $redeemedCount,
        private ?array $targetTiers,
        private ?array $targetSegments,
        private bool $isActive,
        private ?DateTimeImmutable $startsAt,
        private ?DateTimeImmutable $endsAt,
        private ?int $maxRedemptionsPerGuest,
        private ?int $maxRedemptionsTotal,
        private ?string $imageUrl,
        private int $sortOrder,
        private ?array $metadata,
        private ?string $correlationId,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt
    ) {
        if ($this->valueAmount !== null && $this->valueAmount < 0) {
            throw new DomainException('Value amount cannot be negative');
        }

        if ($this->redeemedCount < 0) {
            throw new DomainException('Redeemed count cannot be negative');
        }

        if ($this->stockQuantity !== null && $this->redeemedCount > $this->stockQuantity) {
            throw new DomainException('Redeemed count cannot exceed stock quantity');
        }

        if ($this->maxRedemptionsTotal !== null && $this->redeemedCount > $this->maxRedemptionsTotal) {
            throw new DomainException('Redeemed count cannot exceed max redemptions total');
        }
    }

    public static function create(
        string $uuid,
        int $tenantId,
        string $loyaltyProgramId,
        string $name,
        LoyaltyRewardType $type,
        Points $pointsCost,
        string $valueType = 'fixed',
        ?float $valueAmount = null,
        ?int $menuItemId = null,
        ?string $itemCode = null,
        ?array $conditions = null,
        ?int $stockQuantity = null,
        ?array $targetTiers = null,
        ?array $targetSegments = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?int $maxRedemptionsPerGuest = null,
        ?int $maxRedemptionsTotal = null,
        ?string $imageUrl = null,
        int $sortOrder = 0,
        ?array $metadata = null,
        ?string $correlationId = null
    ): self {
        return new self(
            id: '0', // Will be set by repository
            uuid: $uuid,
            tenantId: $tenantId,
            loyaltyProgramId: $loyaltyProgramId,
            name: $name,
            description: null,
            type: $type,
            pointsCost: $pointsCost,
            valueType: $valueType,
            valueAmount: $valueAmount,
            menuItemId: $menuItemId,
            itemCode: $itemCode,
            conditions: $conditions,
            stockQuantity: $stockQuantity,
            redeemedCount: 0,
            targetTiers: $targetTiers,
            targetSegments: $targetSegments,
            isActive: true,
            startsAt: $startsAt,
            endsAt: $endsAt,
            maxRedemptionsPerGuest: $maxRedemptionsPerGuest,
            maxRedemptionsTotal: $maxRedemptionsTotal,
            imageUrl: $imageUrl,
            sortOrder: $sortOrder,
            metadata: $metadata,
            correlationId: $correlationId,
            createdAt: new DateTimeImmutable(),
            updatedAt: null,
            deletedAt: null
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

    public function getLoyaltyProgramId(): string
    {
        return $this->loyaltyProgramId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): LoyaltyRewardType
    {
        return $this->type;
    }

    public function getPointsCost(): Points
    {
        return $this->pointsCost;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function getValueAmount(): ?float
    {
        return $this->valueAmount;
    }

    public function getMenuItemId(): ?int
    {
        return $this->menuItemId;
    }

    public function getItemCode(): ?string
    {
        return $this->itemCode;
    }

    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function getRedeemedCount(): int
    {
        return $this->redeemedCount;
    }

    public function getTargetTiers(): ?array
    {
        return $this->targetTiers;
    }

    public function getTargetSegments(): ?array
    {
        return $this->targetSegments;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getMaxRedemptionsPerGuest(): ?int
    {
        return $this->maxRedemptionsPerGuest;
    }

    public function getMaxRedemptionsTotal(): ?int
    {
        return $this->maxRedemptionsTotal;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
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

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isCurrentlyAvailable(DateTimeImmutable $now = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->stockQuantity !== null && $this->redeemedCount >= $this->stockQuantity) {
            return false;
        }

        if ($this->maxRedemptionsTotal !== null && $this->redeemedCount >= $this->maxRedemptionsTotal) {
            return false;
        }

        $now = $now ?? new DateTimeImmutable();

        if ($this->startsAt !== null && $now < $this->startsAt) {
            return false;
        }

        if ($this->endsAt !== null && $now > $this->endsAt) {
            return false;
        }

        return true;
    }

    public function isAvailableToTier(string $tierSlug): bool
    {
        if ($this->targetTiers === null || empty($this->targetTiers)) {
            return true; // Available to all tiers
        }

        return in_array($tierSlug, $this->targetTiers, true);
    }

    public function isAvailableToSegment(string $segment): bool
    {
        if ($this->targetSegments === null || empty($this->targetSegments)) {
            return true; // Available to all segments
        }

        return in_array($segment, $this->targetSegments, true);
    }

    public function canBeRedeemedByGuest(int $guestRedemptions): bool
    {
        if ($this->maxRedemptionsPerGuest === null) {
            return true;
        }

        return $guestRedemptions < $this->maxRedemptionsPerGuest;
    }

    public function incrementRedemption(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            name: $this->name,
            description: $this->description,
            type: $this->type,
            pointsCost: $this->pointsCost,
            valueType: $this->valueType,
            valueAmount: $this->valueAmount,
            menuItemId: $this->menuItemId,
            itemCode: $this->itemCode,
            conditions: $this->conditions,
            stockQuantity: $this->stockQuantity,
            redeemedCount: $this->redeemedCount + 1,
            targetTiers: $this->targetTiers,
            targetSegments: $this->targetSegments,
            isActive: $this->isActive,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            maxRedemptionsPerGuest: $this->maxRedemptionsPerGuest,
            maxRedemptionsTotal: $this->maxRedemptionsTotal,
            imageUrl: $this->imageUrl,
            sortOrder: $this->sortOrder,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            deletedAt: $this->deletedAt
        );
    }

    public function matchesCondition(string $key, mixed $value): bool
    {
        if ($this->conditions === null) {
            return true;
        }

        if (!isset($this->conditions[$key])) {
            return true;
        }

        $condition = $this->conditions[$key];

        if (is_array($condition)) {
            return in_array($value, $condition, true);
        }

        return $condition == $value;
    }
}
