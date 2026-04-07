<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Hotels\Domain\Entities\Booking;
use App\Domains\Hotels\Domain\Repositories\BookingRepositoryInterface;
use App\Domains\Hotels\Domain\ValueObjects\BookingId;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models\BookingModel;
use Illuminate\Support\Collection;
use App\Application\Exceptions\NotFoundException;
use Carbon\Carbon;

final class EloquentBookingRepository implements BookingRepositoryInterface
{
    public function find(BookingId $id): ?Booking
    {
        $bookingModel = BookingModel::find($id->toString());

        if (!$bookingModel) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        return $bookingModel->toDomainEntity();
    }

    public function findByRoomAndDateRange(RoomId $roomId, Carbon $checkInDate, Carbon $checkOutDate): ?Booking
    {
        $bookingModel = BookingModel::where('room_id', $roomId->toString())
            ->where(function ($query) use ($checkInDate, $checkOutDate) {
                $query->where('check_in_date', '<', $checkOutDate)
                      ->where('check_out_date', '>', $checkInDate);
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->first();

        return $bookingModel ? $bookingModel->toDomainEntity() : null;
    }

    public function findByHotel(HotelId $hotelId): Collection
    {
        return BookingModel::where('hotel_id', $hotelId->toString())
            ->get()
            ->map(fn (BookingModel $model) => $model->toDomainEntity());
    }

    public function save(Booking $booking): void
    {
        $bookingModel = BookingModel::find($booking->getId()->toString());

        if (!$bookingModel) {
            $bookingModel = new BookingModel();
            $bookingModel->id = $booking->getId()->toString();
        }

        $bookingModel->fill($booking->toArray());
        $bookingModel->save();
    }

    public function delete(BookingId $id): bool
    {
        $bookingModel = BookingModel::find($id->toString());

        if (!$bookingModel) {
            throw new NotFoundException('Booking not found.');
        }

        return $bookingModel->delete();
    }
}
