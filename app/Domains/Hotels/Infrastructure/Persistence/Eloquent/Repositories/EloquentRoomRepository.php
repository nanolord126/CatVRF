<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domains\Hotels\Domain\Entities\Room;
use App\Domains\Hotels\Domain\Repositories\RoomRepositoryInterface;
use App\Domains\Hotels\Domain\ValueObjects\HotelId;
use App\Domains\Hotels\Domain\ValueObjects\RoomId;
use App\Domains\Hotels\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use Illuminate\Support\Collection;
use App\Application\Exceptions\NotFoundException;
use Carbon\Carbon;

final class EloquentRoomRepository implements RoomRepositoryInterface
{
    public function find(RoomId $id): ?Room
    {
        $roomModel = RoomModel::find($id->toString());

        if (!$roomModel) {
            throw new \DomainException('Unexpected null return in ' . __METHOD__);
        }

        return $roomModel->toDomainEntity();
    }

    public function findByHotel(HotelId $hotelId): Collection
    {
        return RoomModel::where('hotel_id', $hotelId->toString())
            ->get()
            ->map(fn (RoomModel $model) => $model->toDomainEntity());
    }

    public function findAvailableRooms(HotelId $hotelId, Carbon $checkInDate, Carbon $checkOutDate, int $capacity): Collection
    {
        return RoomModel::where('hotel_id', $hotelId->toString())
            ->where('capacity', '>=', $capacity)
            ->whereDoesntHave('bookings', function ($query) use ($checkInDate, $checkOutDate) {
                $query->where(function ($q) use ($checkInDate, $checkOutDate) {
                    $q->where('check_in_date', '<', $checkOutDate)
                      ->where('check_out_date', '>', $checkInDate);
                });
            })
            ->get()
            ->map(fn (RoomModel $model) => $model->toDomainEntity());
    }

    public function save(Room $room): void
    {
        $roomModel = RoomModel::find($room->getId()->toString());

        if (!$roomModel) {
            $roomModel = new RoomModel();
            $roomModel->id = $room->getId()->toString();
        }

        $roomModel->fill($room->toArray());
        $roomModel->save();
    }

    public function delete(RoomId $id): bool
    {
        $roomModel = RoomModel::find($id->toString());

        if (!$roomModel) {
            throw new NotFoundException('Room not found.');
        }

        return $roomModel->delete();
    }
}
