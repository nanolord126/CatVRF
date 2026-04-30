<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Repositories;

use Modules\RealEstate\Domain\Entities\PropertyBooking;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;

interface PropertyBookingRepositoryInterface
{
    public function save(PropertyBooking $booking): PropertyBooking;

    public function findById(int $id): ?PropertyBooking;

    public function findByProperty(PropertyId $propertyId): array;

    public function findByUser(int $userId): array;

    public function delete(int $id): void;
}
