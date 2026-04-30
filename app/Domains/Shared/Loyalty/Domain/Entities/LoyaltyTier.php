<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class LoyaltyTier
{
    public function __construct(
        private string $id,
        private string $uuid,
        private int $tenantId,
        private string $loyaltyProgramId,
        private string $name,
        private string $slug,
        private ?string $description,
        private Points $minPoints,
        private ?CurrencyAmount $minSpend,
        private ?int $minVisits,
        private float $pointMultiplier,
        private float $discountPercentage,
        private ?array $privileges,
        private ?string $color,
        private ?string $icon,
        private int $sortOrder,
        private bool $isActive,
        private ?array $metadata,
        private ?string $correlationId,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt
    ) {
        if ($this->pointMultiplier < 0) {
            throw new DomainException('Point multiplier cannot be negative');
        }

        if ($this->discountPercentage < 0 || $this->discountPercentage > 1) {
            throw new DomainException('Discount percentage must be between 0 and 1');
        }

        if ($this->minVisits !== null && $this->minVisits < 0) {
            throw new DomainException('Min visits cannot be negative');
        }
    }

    public static function create(
        string $uuid,
        int $tenantId,
        string $loyaltyProgramId,
        string $name,
        string $slug,
        ?string $description = null,
        Points $minPoints = null,
        ?CurrencyAmount $minSpend = null,
        ?int $minVisits = null,
        float $pointMultiplier = 1.0,
        float $discountPercentage = 0.0,
        ?array $privileges = null,
        ?string $color = null,
        ?string $icon = null,
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
            slug: $slug,
            description: $description,
            minPoints: $minPoints ?? Points::zero(),
            minSpend: $minSpend,
            minVisits: $minVisits,
            pointMultiplier: $pointMultiplier,
            discountPercentage: $discountPercentage,
            privileges: $privileges,
            color: $color,
            icon: $icon,
            sortOrder: $sortOrder,
            isActive: true,
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMinPoints(): Points
    {
        return $this->minPoints;
    }

    public function getMinSpend(): ?CurrencyAmount
    {
        return $this->minSpend;
    }

    public function getMinVisits(): ?int
    {
        return $this->minVisits;
    }

    public function getPointMultiplier(): float
    {
        return $this->pointMultiplier;
    }

    public function getDiscountPercentage(): float
    {
        return $this->discountPercentage;
    }

    public function getPrivileges(): ?array
    {
        return $this->privileges;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
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

    public function hasPrivilege(string $privilege): bool
    {
        if ($this->privileges === null) {
            return false;
        }

        return in_array($privilege, $this->privileges, true);
    }

    public function qualifies(Points $points, ?CurrencyAmount $totalSpend = null, ?int $totalVisits = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($points->isLessThan($this->minPoints)) {
            return false;
        }

        if ($this->minSpend !== null && $totalSpend !== null) {
            if ($totalSpend->isLessThan($this->minSpend)) {
                return false;
            }
        }

        if ($this->minVisits !== null && $totalVisits !== null) {
            if ($totalVisits < $this->minVisits) {
                return false;
            }
        }

        return true;
    }

    public function applyMultiplier(Points $points): Points
    {
        return $points->multiply($this->pointMultiplier);
    }
}
