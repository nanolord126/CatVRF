<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Loyalty\Domain\Enums\VerticalType;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;

final readonly class LoyaltyProgram
{
    public function __construct(
        private string $id,
        private string $uuid,
        private int $tenantId,
        private ?int $businessGroupId,
        private string $name,
        private ?string $description,
        private VerticalType $verticalType,
        private bool $isActive,
        private float $basePointsPerCurrency,
        private float $pointsToCurrencyRate,
        private float $signupBonusPoints,
        private float $birthdayBonusPoints,
        private float $referralBonusPoints,
        private bool $tierSystemEnabled,
        private ?array $tierConfig,
        private bool $pointsExpire,
        private ?int $pointsExpirationDays,
        private ?DateTimeImmutable $startsAt,
        private ?DateTimeImmutable $endsAt,
        private ?array $metadata,
        private ?string $correlationId,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt
    ) {
        if ($this->basePointsPerCurrency < 0) {
            throw new DomainException('Base points per currency cannot be negative');
        }

        if ($this->pointsToCurrencyRate < 0) {
            throw new DomainException('Points to currency rate cannot be negative');
        }

        if ($this->signupBonusPoints < 0) {
            throw new DomainException('Signup bonus points cannot be negative');
        }

        if ($this->birthdayBonusPoints < 0) {
            throw new DomainException('Birthday bonus points cannot be negative');
        }

        if ($this->referralBonusPoints < 0) {
            throw new DomainException('Referral bonus points cannot be negative');
        }

        if ($this->pointsExpire && $this->pointsExpirationDays === null) {
            throw new DomainException('Points expiration days must be set when points expire');
        }

        if ($this->pointsExpirationDays !== null && $this->pointsExpirationDays < 1) {
            throw new DomainException('Points expiration days must be at least 1');
        }
    }

    public static function create(
        string $uuid,
        int $tenantId,
        ?int $businessGroupId,
        string $name,
        ?string $description,
        VerticalType $verticalType,
        float $basePointsPerCurrency = 1.0,
        float $pointsToCurrencyRate = 0.01,
        float $signupBonusPoints = 0.0,
        float $birthdayBonusPoints = 0.0,
        float $referralBonusPoints = 0.0,
        bool $tierSystemEnabled = true,
        ?array $tierConfig = null,
        bool $pointsExpire = false,
        ?int $pointsExpirationDays = null,
        ?DateTimeImmutable $startsAt = null,
        ?DateTimeImmutable $endsAt = null,
        ?array $metadata = null,
        ?string $correlationId = null
    ): self {
        return new self(
            id: '0', // Will be set by repository
            uuid: $uuid,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            name: $name,
            description: $description,
            verticalType: $verticalType,
            isActive: true,
            basePointsPerCurrency: $basePointsPerCurrency,
            pointsToCurrencyRate: $pointsToCurrencyRate,
            signupBonusPoints: $signupBonusPoints,
            birthdayBonusPoints: $birthdayBonusPoints,
            referralBonusPoints: $referralBonusPoints,
            tierSystemEnabled: $tierSystemEnabled,
            tierConfig: $tierConfig,
            pointsExpire: $pointsExpire,
            pointsExpirationDays: $pointsExpirationDays,
            startsAt: $startsAt,
            endsAt: $endsAt,
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

    public function getBusinessGroupId(): ?int
    {
        return $this->businessGroupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getVerticalType(): VerticalType
    {
        return $this->verticalType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getBasePointsPerCurrency(): float
    {
        return $this->basePointsPerCurrency;
    }

    public function getPointsToCurrencyRate(): float
    {
        return $this->pointsToCurrencyRate;
    }

    public function getSignupBonusPoints(): float
    {
        return $this->signupBonusPoints;
    }

    public function getBirthdayBonusPoints(): float
    {
        return $this->birthdayBonusPoints;
    }

    public function getReferralBonusPoints(): float
    {
        return $this->referralBonusPoints;
    }

    public function isTierSystemEnabled(): bool
    {
        return $this->tierSystemEnabled;
    }

    public function getTierConfig(): ?array
    {
        return $this->tierConfig;
    }

    public function doPointsExpire(): bool
    {
        return $this->pointsExpire;
    }

    public function getPointsExpirationDays(): ?int
    {
        return $this->pointsExpirationDays;
    }

    public function getStartsAt(): ?DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): ?DateTimeImmutable
    {
        return $this->endsAt;
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

        $now = $now ?? new DateTimeImmutable();

        if ($this->startsAt !== null && $now < $this->startsAt) {
            return false;
        }

        if ($this->endsAt !== null && $now > $this->endsAt) {
            return false;
        }

        return true;
    }

    public function calculatePointsFromAmount(CurrencyAmount $amount): float
    {
        return $amount->getValue() * $this->basePointsPerCurrency;
    }

    public function calculateCurrencyFromPoints(float $points): float
    {
        return $points * $this->pointsToCurrencyRate;
    }
}
