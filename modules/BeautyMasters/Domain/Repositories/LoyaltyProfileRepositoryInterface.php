<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\LoyaltyProfile;
use Illuminate\Support\Collection;

interface LoyaltyProfileRepositoryInterface
{
    public function findById(int $id): ?LoyaltyProfile;

    public function findByClientAndVenue(int $clientId, int $venueId): ?LoyaltyProfile;

    public function findByVenueId(int $venueId, ?string $tier = null): Collection;

    public function findByTier(int $venueId, string $tier): Collection;

    public function save(LoyaltyProfile $profile): LoyaltyProfile;

    public function delete(int $id): bool;

    public function updateBalance(int $id, int $points): bool;

    public function updateTier(int $id, string $tier): bool;
}
