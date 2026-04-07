<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\Entities;

use App\Domains\Auto\Taxi\Domain\Enums\RideStatusEnum;
use App\Domains\Auto\Taxi\Domain\Events\RideAccepted;
use App\Domains\Auto\Taxi\Domain\Events\RideFinished;
use App\Domains\Auto\Taxi\Domain\Events\RideRequested;
use App\Domains\Auto\Taxi\Domain\Events\RideStarted;
use App\Domains\Auto\Taxi\Domain\ValueObjects\Coordinate;
use App\Domains\Auto\Taxi\Domain\ValueObjects\DriverId;
use App\Domains\Auto\Taxi\Domain\ValueObjects\RideId;
use App\Shared\Domain\Entity;

final class Ride extends Entity
{
    public function __construct(
        private readonly RideId $id,
        private readonly int $clientId,
        private ?DriverId $driverId,
        private RideStatusEnum $status,
        private readonly Coordinate $pickupLocation,
        private readonly Coordinate $dropoffLocation,
        private ?int $price,
        private readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt) {

    }

    public static function request(
        RideId $id,
        int $clientId,
        Coordinate $pickupLocation,
        Coordinate $dropoffLocation
    ): self {
        $now = new \DateTimeImmutable();
        $ride = new self(
            $id,
            $clientId,
            null,
            RideStatusEnum::PENDING,
            $pickupLocation,
            $dropoffLocation,
            null,
            $now,
            $now
        );

        $ride->record(new RideRequested($ride->getId(), $ride->getClientId()));

        return $ride;
    }

    public function accept(DriverId $driverId, int $price): void
    {
        if ($this->status !== RideStatusEnum::PENDING) {
            // throw exception
        }
        $this->driverId = $driverId;
        $this->price = $price;
        $this->status = RideStatusEnum::ACCEPTED;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new RideAccepted($this->id, $this->driverId, $this->price));
    }

    public function start(): void
    {
        if ($this->status !== RideStatusEnum::ACCEPTED) {
            // throw exception
        }
        $this->status = RideStatusEnum::IN_PROGRESS;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new RideStarted($this->id));
    }

    public function finish(): void
    {
        if ($this->status !== RideStatusEnum::IN_PROGRESS) {
            // throw exception
        }
        $this->status = RideStatusEnum::COMPLETED;
        $this->updatedAt = new \DateTimeImmutable();

        $this->record(new RideFinished($this->id));
    }

    public function getId(): RideId
    {
        return $this->id;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }
    
    public function getDriverId(): ?DriverId
    {
        return $this->driverId;
    }

    public function getStatus(): RideStatusEnum
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'client_id' => $this->clientId,
            'driver_id' => $this->driverId?->toString(),
            'status' => $this->status->value,
            'pickup_location' => $this->pickupLocation->toArray(),
            'dropoff_location' => $this->dropoffLocation->toArray(),
            'price' => $this->price,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
