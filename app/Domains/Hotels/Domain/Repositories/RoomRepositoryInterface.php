<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/component
 */


namespace App\Domains\Hotels\Domain\Repositories;

use App\Domains\Hotels\Domain\Entities\Room;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use Illuminate\Support\Collection;

interface RoomRepositoryInterface
{
    public function find(RoomId $id): ?Room;

    public function findByHotel(HotelId $hotelId): Collection;

    /**
     * @param HotelId $hotelId
     * @param \Carbon\Carbon $checkInDate
     * @param \Carbon\Carbon $checkOutDate
     * @param int $capacity
     * @return Collection<Room>
     */
    public function findAvailableRooms(HotelId $hotelId, \Carbon\Carbon $checkInDate, \Carbon\Carbon $checkOutDate, int $capacity): Collection;

    public function save(Room $room): void;

    public function delete(RoomId $id): bool;
}
