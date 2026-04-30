<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Repositories;

use Modules\Flowers\Domain\Entities\DeliverySlot;
use Carbon\CarbonImmutable;

interface DeliverySlotRepositoryInterface
{
    public function findById(int $id): ?DeliverySlot;

    public function save(DeliverySlot $slot): DeliverySlot;

    public function delete(int $id): void;

    public function getByVenue(int $venueId, CarbonImmutable $date): array;

    public function getAvailableSlots(int $venueId, CarbonImmutable $date): array;

    public function bookSlot(int $slotId, int $quantity = 1): DeliverySlot;

    public function releaseBooking(int $slotId, int $quantity = 1): DeliverySlot;
}
