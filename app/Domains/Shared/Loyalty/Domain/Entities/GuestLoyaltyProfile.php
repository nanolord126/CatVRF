<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class GuestLoyaltyProfile
{
    public function __construct(
        private string $id,
        private string $uuid,
        private int $tenantId,
        private string $loyaltyProgramId,
        private int $guestId,
        private ?int $userId,
        private ?string $currentTierId,
        private ?DateTimeImmutable $tierUpdatedAt,
        private Points $availablePoints,
        private Points $earnedPoints,
        private Points $redeemedPoints,
        private CurrencyAmount $totalSpend,
        private int $totalVisits,
        private bool $isActive,
        private ?DateTimeImmutable $enrolledAt,
        private ?DateTimeImmutable $lastActivityAt,
        private ?DateTimeImmutable $birthday,
        private bool $birthdayBonusReceived,
        private ?int $birthdayBonusYear,
        private ?int $referredBy,
        private ?array $preferences,
        private ?array $metadata,
        private ?string $correlationId,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt
    ) {
        if ($this->totalVisits < 0) {
            throw new DomainException('Total visits cannot be negative');
        }
    }

    public static function enroll(
        string $uuid,
        int $tenantId,
        string $loyaltyProgramId,
        int $guestId,
        ?int $userId = null,
        Points $signupBonus = null,
        ?DateTimeImmutable $birthday = null,
        ?int $referredBy = null,
        ?array $preferences = null,
        ?array $metadata = null,
        ?string $correlationId = null
    ): self {
        $availablePoints = $signupBonus ?? Points::zero();
        $earnedPoints = $signupBonus ?? Points::zero();

        return new self(
            id: '0', // Will be set by repository
            uuid: $uuid,
            tenantId: $tenantId,
            loyaltyProgramId: $loyaltyProgramId,
            guestId: $guestId,
            userId: $userId,
            currentTierId: null,
            tierUpdatedAt: null,
            availablePoints: $availablePoints,
            earnedPoints: $earnedPoints,
            redeemedPoints: Points::zero(),
            totalSpend: CurrencyAmount::zero(),
            totalVisits: 0,
            isActive: true,
            enrolledAt: new DateTimeImmutable(),
            lastActivityAt: new DateTimeImmutable(),
            birthday: $birthday,
            birthdayBonusReceived: false,
            birthdayBonusYear: null,
            referredBy: $referredBy,
            preferences: $preferences,
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

    public function getGuestId(): int
    {
        return $this->guestId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCurrentTierId(): ?string
    {
        return $this->currentTierId;
    }

    public function getTierUpdatedAt(): ?DateTimeImmutable
    {
        return $this->tierUpdatedAt;
    }

    public function getAvailablePoints(): Points
    {
        return $this->availablePoints;
    }

    public function getEarnedPoints(): Points
    {
        return $this->earnedPoints;
    }

    public function getRedeemedPoints(): Points
    {
        return $this->redeemedPoints;
    }

    public function getTotalSpend(): CurrencyAmount
    {
        return $this->totalSpend;
    }

    public function getTotalVisits(): int
    {
        return $this->totalVisits;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getEnrolledAt(): ?DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function getLastActivityAt(): ?DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    public function getBirthday(): ?DateTimeImmutable
    {
        return $this->birthday;
    }

    public function isBirthdayBonusReceived(): bool
    {
        return $this->birthdayBonusReceived;
    }

    public function getBirthdayBonusYear(): ?int
    {
        return $this->birthdayBonusYear;
    }

    public function getReferredBy(): ?int
    {
        return $this->referredBy;
    }

    public function getPreferences(): ?array
    {
        return $this->preferences;
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

    public function canSpendPoints(Points $points): bool
    {
        return $this->availablePoints->isGreaterThanOrEqual($points);
    }

    public function isEligibleForBirthdayBonus(int $year): bool
    {
        if ($this->birthday === null) {
            return false;
        }

        if ($this->birthdayBonusReceived && $this->birthdayBonusYear === $year) {
            return false;
        }

        $birthdayThisYear = (new DateTimeImmutable())
            ->setDate($year, (int) $this->birthday->format('m'), (int) $this->birthday->format('d'));

        $now = new DateTimeImmutable();
        return $now >= $birthdayThisYear;
    }

    public function withPointsAdded(Points $points): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            guestId: $this->guestId,
            userId: $this->userId,
            currentTierId: $this->currentTierId,
            tierUpdatedAt: $this->tierUpdatedAt,
            availablePoints: $this->availablePoints->add($points),
            earnedPoints: $this->earnedPoints->add($points),
            redeemedPoints: $this->redeemedPoints,
            totalSpend: $this->totalSpend,
            totalVisits: $this->totalVisits,
            isActive: $this->isActive,
            enrolledAt: $this->enrolledAt,
            lastActivityAt: new DateTimeImmutable(),
            birthday: $this->birthday,
            birthdayBonusReceived: $this->birthdayBonusReceived,
            birthdayBonusYear: $this->birthdayBonusYear,
            referredBy: $this->referredBy,
            preferences: $this->preferences,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            deletedAt: $this->deletedAt
        );
    }

    public function withPointsRedeemed(Points $points): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            guestId: $this->guestId,
            userId: $this->userId,
            currentTierId: $this->currentTierId,
            tierUpdatedAt: $this->tierUpdatedAt,
            availablePoints: $this->availablePoints->subtract($points),
            earnedPoints: $this->earnedPoints,
            redeemedPoints: $this->redeemedPoints->add($points),
            totalSpend: $this->totalSpend,
            totalVisits: $this->totalVisits,
            isActive: $this->isActive,
            enrolledAt: $this->enrolledAt,
            lastActivityAt: new DateTimeImmutable(),
            birthday: $this->birthday,
            birthdayBonusReceived: $this->birthdayBonusReceived,
            birthdayBonusYear: $this->birthdayBonusYear,
            referredBy: $this->referredBy,
            preferences: $this->preferences,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            deletedAt: $this->deletedAt
        );
    }

    public function withSpendAdded(CurrencyAmount $amount): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            guestId: $this->guestId,
            userId: $this->userId,
            currentTierId: $this->currentTierId,
            tierUpdatedAt: $this->tierUpdatedAt,
            availablePoints: $this->availablePoints,
            earnedPoints: $this->earnedPoints,
            redeemedPoints: $this->redeemedPoints,
            totalSpend: $this->totalSpend->add($amount),
            totalVisits: $this->totalVisits + 1,
            isActive: $this->isActive,
            enrolledAt: $this->enrolledAt,
            lastActivityAt: new DateTimeImmutable(),
            birthday: $this->birthday,
            birthdayBonusReceived: $this->birthdayBonusReceived,
            birthdayBonusYear: $this->birthdayBonusYear,
            referredBy: $this->referredBy,
            preferences: $this->preferences,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            deletedAt: $this->deletedAt
        );
    }

    public function withTierUpdated(string $tierId): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            guestId: $this->guestId,
            userId: $this->userId,
            currentTierId: $tierId,
            tierUpdatedAt: new DateTimeImmutable(),
            availablePoints: $this->availablePoints,
            earnedPoints: $this->earnedPoints,
            redeemedPoints: $this->redeemedPoints,
            totalSpend: $this->totalSpend,
            totalVisits: $this->totalVisits,
            isActive: $this->isActive,
            enrolledAt: $this->enrolledAt,
            lastActivityAt: $this->lastActivityAt,
            birthday: $this->birthday,
            birthdayBonusReceived: $this->birthdayBonusReceived,
            birthdayBonusYear: $this->birthdayBonusYear,
            referredBy: $this->referredBy,
            preferences: $this->preferences,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            deletedAt: $this->deletedAt
        );
    }

    public function withBirthdayBonusMarked(int $year): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            loyaltyProgramId: $this->loyaltyProgramId,
            guestId: $this->guestId,
            userId: $this->userId,
            currentTierId: $this->currentTierId,
            tierUpdatedAt: $this->tierUpdatedAt,
            availablePoints: $this->availablePoints,
            earnedPoints: $this->earnedPoints,
            redeemedPoints: $this->redeemedPoints,
            totalSpend: $this->totalSpend,
            totalVisits: $this->totalVisits,
            isActive: $this->isActive,
            enrolledAt: $this->enrolledAt,
            lastActivityAt: $this->lastActivityAt,
            birthday: $this->birthday,
            birthdayBonusReceived: true,
            birthdayBonusYear: $year,
            referredBy: $this->referredBy,
            preferences: $this->preferences,
            metadata: $this->metadata,
            correlationId: $this->correlationId,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            deletedAt: $this->deletedAt
        );
    }
}
