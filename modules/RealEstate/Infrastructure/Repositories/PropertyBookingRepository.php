<?php

declare(strict_types=1);

namespace Modules\RealEstate\Infrastructure\Repositories;

use Modules\RealEstate\Domain\Entities\PropertyBooking;
use Modules\RealEstate\Domain\Repositories\PropertyBookingRepositoryInterface;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;

final class PropertyBookingRepository implements PropertyBookingRepositoryInterface
{
    public function save(PropertyBooking $booking): PropertyBooking
    {
        // Placeholder implementation
        return $booking;
    }

    public function findById(int $id): ?PropertyBooking
    {
        // Placeholder implementation
        return null;
    }

    public function findByProperty(PropertyId $propertyId): array
    {
        // Placeholder implementation
        return [];
    }

    public function findByUser(int $userId): array
    {
        // Placeholder implementation
        return [];
    }

    public function delete(int $id): void
    {
        // Placeholder implementation
    }
}
