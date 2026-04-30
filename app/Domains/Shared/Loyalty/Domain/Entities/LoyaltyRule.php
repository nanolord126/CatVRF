<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Loyalty\Domain\Enums\LoyaltyRuleType;

final readonly class LoyaltyRule
{
    public function __construct(
        private string $id,
        private string $uuid,
        private int $tenantId,
        private string $loyaltyProgramId,
        private string $name,
        private ?string $description,
        private LoyaltyRuleType $type,
        private ?array $conditions,
        private string $calculationType,
        private ?float $pointsValue,
        private float $pointMultiplier,
        private ?int $maxUsesPerGuest,
        private ?int $maxUsesTotal,
        private int $currentUses,
        private bool $isActive,
        private ?DateTimeImmutable $startsAt,
        private ?DateTimeImmutable $endsAt,
        private ?array $targetTiers,
        private ?array $targetSegments,
        private int $priority,
        private ?array $metadata,
        private ?string $correlationId,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt
    ) {
        if ($this->pointMultiplier < 0) {
            throw new DomainException('Point multiplier cannot be negative');
        }

        if ($this->currentUses < 0) {
            throw new DomainException('Current uses cannot be negative');
        }

        if ($this->maxUsesTotal !== null && $this->currentUses > $this->maxUsesTotal) {
            throw new DomainException('Current uses cannot exceed max uses total');
        }

        if ($this->pointsValue !== null && $this->pointsValue < 0) {
            throw new DomainException('Points value cannot be negative');
        }
    }

    public static function create(
        string $uuid,
        int $tenantId,
        string $loyaltyProgramId,
        string $name,
        LoyaltyRuleType $type,
        ?string $description = null,
        ?array $conditions = null,
        string $calculationType = 'percentage',
        ?float $pointsValue = null,
        float $pointMultiplier = 1.0,
        ?int $maxUsesPerGuest = null,
        ?int $maxUsesTotal = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?array $targetTiers = null,
        ?array $targetSegments = null,
        int $priority = 0,
        ?array $metadata = null,
        ?string $correlationId = null
    ): self {
        return new self(
            id: '0', // Will be set by repository
            uuid: $uuid,
            tenantId: $tenantId,
            loyaltyProgramId: $loyaltyProgramId,
            name: $name,
            description: $description,
            type: $type,
            conditions: $conditions,
            calculationType: $calculationType,
            pointsValue: $pointsValue,
            pointMultiplier: $pointMultiplier,
            maxUsesPerGuest: $maxUsesPerGuest,
            maxUsesTotal: $maxUsesTotal,
            currentUses: 0,
            isActive: true,
            startsAt: $startsAt,
            endsAt: $endsAt,
            targetTiers: $targetTiers,
            targetSegments: $targetSegments,
            priority: $priority,
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

    public function getType(): LoyaltyRuleType
    {
        return $this->type;
    }

    public function getConditions(): ?array
    {
        return $this->conditions;
    }

    public function getCalculationType(): string
    {
        return $this->calculationType;
    }

    public function getPointsValue(): ?float
    {
        return $this->pointsValue;
    }

    public function getPointMultiplier(): float
    {
        return $this->pointMultiplier;
    }

    public function getMaxUsesPerGuest(): ?int
    {
        return $this->maxUsesPerGuest;
    }

    public function getMaxUsesTotal(): ?int
    {
        return $this->maxUsesTotal;
    }

    public function getCurrentUses(): int
    {
        return $this->currentUses;
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

    public function getTargetTiers(): ?array
    {
        return $this->targetTiers;
    }

    public function getTargetSegments(): ?array
    {
        return $this->targetSegments;
    }

    public function getPriority(): int
    {
        return $this->priority;
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

    public function isCurrentlyActive(DateTimeImmutable $now = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->maxUsesTotal !== null && $this->currentUses >= $this->maxUsesTotal) {
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

    public function appliesToTier(string $tierSlug): bool
    {
        if ($this->targetTiers === null || empty($this->targetTiers)) {
            return true; // Applies to all tiers
        }

        return in_array($tierSlug, $this->targetTiers, true);
    }

    public function appliesToSegment(string $segment): bool
    {
        if ($this->targetSegments === null || empty($this->targetSegments)) {
            return true; // Applies to all segments
        }

        return in_array($segment, $this->targetSegments, true);
    }

    public function canBeUsedByGuest(int $guestUses): bool
    {
        if ($this->maxUsesPerGuest === null) {
            return true;
        }

        return $guestUses < $this->maxUsesPerGuest;
    }

    public function incrementUsage(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            name: $this->name,
            description: $this->description,
            type: $this->type,
            conditions: $this->conditions,
            calculationType: $this->calculationType,
            pointsValue: $this->pointsValue,
            pointMultiplier: $this->pointMultiplier,
            maxUsesPerGuest: $this->maxUsesPerGuest,
            maxUsesTotal: $this->maxUsesTotal,
            currentUses: $this->currentUses + 1,
            isActive: $this->isActive,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            targetTiers: $this->targetTiers,
            targetSegments: $this->targetSegments,
            priority: $this->priority,
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
