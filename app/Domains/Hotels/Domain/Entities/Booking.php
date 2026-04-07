<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Domain\Entities;

use App\Domains\Hotels\Domain\ValueObjects\BookingId;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Domain\Enums\BookingStatus;
use App\Shared\Domain\Entities\Entity;
use Carbon\Carbon;

final class Booking extends Entity
{
    /**
     * @param BookingId $id
     * @param HotelId $hotelId
     * @param RoomId $roomId
     * @param int $userId
     * @param Carbon $checkInDate
     * @param Carbon $checkOutDate
     * @param int $totalPrice
     * @param BookingStatus $status
     * @param string|null $correlationId
     */
    public function __construct(
        private readonly BookingId $id,
        private readonly HotelId $hotelId,
        private readonly RoomId $roomId,
        private readonly int $userId,
        private readonly Carbon $checkInDate,
        private readonly Carbon $checkOutDate,
        private readonly int $totalPrice,
        private BookingStatus $status = BookingStatus::PENDING,
        private ?string $correlationId = null
    ) {
        if ($checkOutDate->isBefore($checkInDate)) {
            throw new \InvalidArgumentException('Check-out date cannot be before check-in date.');
        }
    }

    public function getId(): BookingId
    {
        return $this->id;
    }

    public function getHotelId(): HotelId
    {
        return $this->hotelId;
    }

    public function getRoomId(): RoomId
    {
        return $this->roomId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCheckInDate(): Carbon
    {
        return $this->checkInDate;
    }

    public function getCheckOutDate(): Carbon
    {
        return $this->checkOutDate;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function confirm(): void
    {
        if ($this->status !== BookingStatus::PENDING) {
            throw new \LogicException('Only pending bookings can be confirmed.');
        }
        $this->status = BookingStatus::CONFIRMED;
    }

    public function cancel(): void
    {
        if ($this->status === BookingStatus::CANCELLED) {
            throw new \LogicException('Booking is already cancelled.');
        }
        $this->status = BookingStatus::CANCELLED;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'hotel_id' => $this->hotelId->toString(),
            'room_id' => $this->roomId->toString(),
            'user_id' => $this->userId,
            'check_in_date' => $this->checkInDate->toIso8601String(),
            'check_out_date' => $this->checkOutDate->toIso8601String(),
            'total_price' => $this->totalPrice,
            'status' => $this->status->value,
            'correlation_id' => $this->correlationId,
        ];
    }
}
