<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Repositories;

use Modules\Hotels\Domain\Entities\Service;
use Modules\Hotels\Domain\ValueObjects\ServiceId;
use Modules\Hotels\Domain\ValueObjects\VenueId;
use Modules\Hotels\Domain\ValueObjects\TenantId;

interface ServiceRepositoryInterface
{
    public function save(Service $service): void;
    public function findById(ServiceId $id): ?Service;
    public function findByVenue(VenueId $venueId): array;
    public function findByTenant(TenantId $tenantId): array;
    public function findAvailableByVenue(VenueId $venueId): array;
    public function delete(ServiceId $id): void;
}
