<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Events;

use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\ValueObjects\Points;

final readonly class GuestEnrolled
{
    public function __construct(
        private string $profileId,
        private string $profileUuid,
        private int $guestId,
        private ?int $userId,
        private int $tenantId,
        private string $programId,
        private Points $signupBonus
    ) {
    }

    public static function fromProfile(GuestLoyaltyProfile $profile): self
    {
        return new self(
            profileId: $profile->getId(),
            profileUuid: $profile->getUuid(),
            guestId: $profile->getGuestId(),
            userId: $profile->getUserId(),
            tenantId: $profile->getTenantId(),
            programId: $profile->getLoyaltyProgramId(),
            signupBonus: $profile->getAvailablePoints()
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

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getProgramId(): string
    {
        return $this->programId;
    }

    public function getSignupBonus(): Points
    {
        return $this->signupBonus;
    }
}
