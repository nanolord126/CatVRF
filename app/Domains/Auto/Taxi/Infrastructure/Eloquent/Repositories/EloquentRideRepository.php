<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Infrastructure\Eloquent\Repositories;

use App\Domains\Auto\Taxi\Domain\Entities\Ride as RideEntity;
use App\Domains\Auto\Taxi\Domain\Enums\RideStatusEnum;
use App\Domains\Auto\Taxi\Domain\Repository\RideRepositoryInterface;
use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use App\Domains\Auto\Taxi\Infrastructure\Eloquent\Models\Ride as RideModel;
use Illuminate\Support\Str;

final class EloquentRideRepository implements RideRepositoryInterface
{
    public function findById(RideId $id): RideEntity
    {
        $rideModel = RideModel::find($id->toString());

        if (!$rideModel) {
            throw new \DomainException("Ride not found: {$id->toString()}");
        }

        return $this->toEntity($rideModel);
    }

    public function save(RideEntity $ride): void
    {
        $rideData = $ride->toArray();

        RideModel::updateOrCreate(
            ['id' => $ride->getId()->toString()],
            [
                'client_id' => $rideData['client_id'],
                'driver_id' => $rideData['driver_id'],
                'status' => $rideData['status'],
                'pickup_location' => $rideData['pickup_location'],
                'dropoff_location' => $rideData['dropoff_location'],
                'price' => $rideData['price'],
                // 'correlation_id' should be handled by a trait or middleware
            ]
        );
    }

    public function getNextId(): RideId
    {
        return new RideId(Str::uuid()->toString());
    }

    private function toEntity(RideModel $model): RideEntity
    {
        return new RideEntity(
            id: new RideId($model->id),
            clientId: $model->client_id,
            driverId: $model->driver_id ? new DriverId($model->driver_id) : null,
            status: RideStatusEnum::from($model->status),
            pickupLocation: new Coordinate(
                latitude: $model->pickup_location['latitude'],
                longitude: $model->pickup_location['longitude']
            ),
            dropoffLocation: new Coordinate(
                latitude: $model->dropoff_location['latitude'],
                longitude: $model->dropoff_location['longitude']
            ),
            price: $model->price,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable()
        );
    }
}
