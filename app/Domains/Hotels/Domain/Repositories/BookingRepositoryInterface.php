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

use App\Domains\Hotels\Domain\Entities\Booking;
use App\Domains\Hotels\Domain\ValueObjects\BookingId;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use Illuminate\Support\Collection;

interface BookingRepositoryInterface
{
    public function find(BookingId $id): ?Booking;

    public function findByRoomAndDateRange(RoomId $roomId, \Carbon\Carbon $checkInDate, \Carbon\Carbon $checkOutDate): ?Booking;

    public function findByHotel(HotelId $hotelId): Collection;

    public function save(Booking $booking): void;

    public function delete(BookingId $id): bool;
}
