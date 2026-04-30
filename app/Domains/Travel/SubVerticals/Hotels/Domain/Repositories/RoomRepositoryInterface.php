<?php

declare(strict_types=1);

/**
 *  — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/component
 */

namespace App\Domains\Travel\SubVerticals\Hotels\Domain\Repositories;

use App\Domains\Hotels\Domain\Entities\Room;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use Illuminate\Support\Collection;
use Carbon\Carbon;

interface RoomRepositoryInterface
{
    public function find(RoomId $id): ?Room;

    public function findByHotel(HotelId $hotelId): Collection;

    /**
     * @return Collection<Room>
     */
    public function findAvailableRooms(HotelId $hotelId, Carbon $checkInDate, Carbon $checkOutDate, int $capacity): Collection;

    public function save(Room $room): void;

    public function delete(RoomId $id): bool;
}
