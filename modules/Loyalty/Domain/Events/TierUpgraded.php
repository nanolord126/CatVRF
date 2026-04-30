<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Events;

use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\Entities\LoyaltyTier;

final readonly class TierUpgraded
{
    public function __construct(
        private string $profileId,
        private string $profileUuid,
        private int $guestId,
        private int $tenantId,
        private string $programId,
        private ?string $previousTierId,
        private string $newTierId,
        private string $newTierName,
        private string $newTierSlug
    ) {
    }

    public static function fromProfile(
        GuestLoyaltyProfile $profile,
        ?string $previousTierId,
        LoyaltyTier $newTier
    ): self {
        return new self(
            profileId: $profile->getId(),
            profileUuid: $profile->getUuid(),
            guestId: $profile->getGuestId(),
            tenantId: $profile->getTenantId(),
            programId: $profile->getLoyaltyProgramId(),
            previousTierId: $previousTierId,
            newTierId: $newTier->getId(),
            newTierName: $newTier->getName(),
            newTierSlug: $newTier->getSlug()
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

    public function getPreviousTierId(): ?string
    {
        return $this->previousTierId;
    }

    public function getNewTierId(): string
    {
        return $this->newTierId;
    }

    public function getNewTierName(): string
    {
        return $this->newTierName;
    }

    public function getNewTierSlug(): string
    {
        return $this->newTierSlug;
    }
}
