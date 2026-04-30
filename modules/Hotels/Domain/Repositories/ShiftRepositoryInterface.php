<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Repositories;

use Modules\Hotels\Domain\Entities\Shift;
use Modules\Hotels\Domain\ValueObjects\ShiftId;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;
use Modules\Hotels\Domain\ValueObjects\UserId;
use Carbon\CarbonImmutable;

interface ShiftRepositoryInterface
{
    public function save(Shift $shift): void;
    public function findById(ShiftId $id): ?Shift;
    public function findByVenue(VenueId $venueId): array;
    public function findByUser(UserId $userId): array;
    public function findByVenueAndDateRange(VenueId $venueId, CarbonImmutable $start, CarbonImmutable $end): array;
    public function findActiveShifts(VenueId $venueId): array;
    public function delete(ShiftId $id): void;
}
