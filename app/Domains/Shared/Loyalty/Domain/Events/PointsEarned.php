<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Events;

use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\Entities\LoyaltyTransaction;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class PointsEarned
{
    public function __construct(
        private string $profileId,
        private string $profileUuid,
        private int $guestId,
        private int $tenantId,
        private string $programId,
        private Points $pointsEarned,
        private Points $balanceAfter,
        private ?string $transactionId,
        private ?string $sourceType,
        private ?int $sourceId,
        private ?string $description
    ) {
    }

    public static function fromTransaction(
        GuestLoyaltyProfile $profile,
        LoyaltyTransaction $transaction
    ): self {
        return new self(
            profileId: $profile->getId(),
            profileUuid: $profile->getUuid(),
            guestId: $profile->getGuestId(),
            tenantId: $profile->getTenantId(),
            programId: $profile->getLoyaltyProgramId(),
            pointsEarned: $transaction->getPointsChange(),
            balanceAfter: $transaction->getBalanceAfter(),
            transactionId: $transaction->getUuid(),
            sourceType: $transaction->getSourceType(),
            sourceId: $transaction->getSourceId(),
            description: $transaction->getDescription()
        );
    }

    public function getProfileId(): string
    {
        return $this->profileId;
    }

    public function getProfileUuid(): string
    {
        return $this->profileUuid;
    }

    public function getGuestId(): int
    {
        return $this->guestId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProgramId(): string
    {
        return $this->programId;
    }

    public function getPointsEarned(): Points
    {
        return $this->pointsEarned;
    }

    public function getBalanceAfter(): Points
    {
        return $this->balanceAfter;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
