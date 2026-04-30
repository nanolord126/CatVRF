<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Events;

use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\Entities\LoyaltyReward;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class RewardRedeemed
{
    public function __construct(
        private string $profileId,
        private string $profileUuid,
        private int $guestId,
        private int $tenantId,
        private string $programId,
        private string $rewardId,
        private string $rewardName,
        private Points $pointsCost,
        private Points $balanceAfter,
        private ?string $transactionId
    ) {
    }

    public static function fromRedemption(
        GuestLoyaltyProfile $profile,
        LoyaltyReward $reward,
        Points $balanceAfter,
        ?string $transactionId = null
    ): self {
        return new self(
            profileId: $profile->getId(),
            profileUuid: $profile->getUuid(),
            guestId: $profile->getGuestId(),
            tenantId: $profile->getTenantId(),
            programId: $profile->getLoyaltyProgramId(),
            rewardId: $reward->getId(),
            rewardName: $reward->getName(),
            pointsCost: $reward->getPointsCost(),
            balanceAfter: $balanceAfter,
            transactionId: $transactionId
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

    public function getRewardId(): string
    {
        return $this->rewardId;
    }

    public function getRewardName(): string
    {
        return $this->rewardName;
    }

    public function getPointsCost(): Points
    {
        return $this->pointsCost;
    }

    public function getBalanceAfter(): Points
    {
        return $this->balanceAfter;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }
}
