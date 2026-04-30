<?php

declare(strict_types=1);

namespace Modules\Loyalty\Domain\Repositories;

use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;

interface GuestLoyaltyProfileRepositoryInterface
{
    public function findById(string $id): ?GuestLoyaltyProfile;

    public function findByUuid(string $uuid): ?GuestLoyaltyProfile;

    public function findByGuestAndProgram(int $guestId, string $programId): ?GuestLoyaltyProfile;

    public function findByUserAndProgram(int $userId, string $programId): ?GuestLoyaltyProfile;

    public function save(GuestLoyaltyProfile $profile): GuestLoyaltyProfile;

    public function delete(string $id): void;
}
