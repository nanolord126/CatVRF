<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Repositories;

use Modules\Hotels\Domain\Entities\LoyaltyProgram;
use Modules\Hotels\Domain\ValueObjects\LoyaltyProgramId;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Modules\Hotels\Domain\Enums\GuestLoyaltyLevel;

interface LoyaltyProgramRepositoryInterface
{
    public function save(LoyaltyProgram $program): void;
    public function findById(LoyaltyProgramId $id): ?LoyaltyProgram;
    public function findByVenue(VenueId $venueId): array;
    public function findByTenant(TenantId $tenantId): array;
    public function findByVenueAndLevel(VenueId $venueId, GuestLoyaltyLevel $level): ?LoyaltyProgram;
    public function findActiveByVenue(VenueId $venueId): array;
    public function delete(LoyaltyProgramId $id): void;
}
