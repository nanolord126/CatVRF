<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Taxi\Domain\ValueObjects\Location;
use Modules\Taxi\Domain\ValueObjects\RideId;
use Modules\Taxi\Domain\ValueObjects\RideStatus;

final readonly class Ride
{
    public function __construct(
        public readonly ?RideId $id,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $driverId,
        public readonly Location $pickupLocation,
        public readonly Location $dropoffLocation,
        public readonly RideStatus $status,
        public readonly float $estimatedPrice,
        public readonly float $actualPrice,
        public readonly CarbonImmutable $requestedAt,
        public readonly ?CarbonImmutable $acceptedAt,
        public readonly ?CarbonImmutable $startedAt,
        public readonly ?CarbonImmutable $completedAt,
        public readonly array $metadata,
    ) {}

    public static function create(
        int $tenantId,
        int $userId,
        int $driverId,
        Location $pickupLocation,
        Location $dropoffLocation,
        float $estimatedPrice,
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            tenantId: $tenantId,
            userId: $userId,
            driverId: $driverId,
            pickupLocation: $pickupLocation,
            dropoffLocation: $dropoffLocation,
            status: RideStatus::requested(),
            estimatedPrice: $estimatedPrice,
            actualPrice: 0.0,
            requestedAt: CarbonImmutable::now(),
            acceptedAt: null,
            startedAt: null,
            completedAt: null,
            metadata: $metadata,
        );
    }

    public function withId(RideId $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            driverId: $this->driverId,
            pickupLocation: $this->pickupLocation,
            dropoffLocation: $this->dropoffLocation,
            status: $this->status,
            estimatedPrice: $this->estimatedPrice,
            actualPrice: $this->actualPrice,
            requestedAt: $this->requestedAt,
            acceptedAt: $this->acceptedAt,
            startedAt: $this->startedAt,
            completedAt: $this->completedAt,
            metadata: $this->metadata,
        );
    }

    public function accept(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            driverId: $this->driverId,
            pickupLocation: $this->pickupLocation,
            dropoffLocation: $this->dropoffLocation,
            status: RideStatus::accepted(),
            estimatedPrice: $this->estimatedPrice,
            actualPrice: $this->actualPrice,
            requestedAt: $this->requestedAt,
            acceptedAt: CarbonImmutable::now(),
            startedAt: null,
            completedAt: null,
            metadata: $this->metadata,
        );
    }

    public function start(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            driverId: $this->driverId,
            pickupLocation: $this->pickupLocation,
            dropoffLocation: $this->dropoffLocation,
            status: RideStatus::inProgress(),
            estimatedPrice: $this->estimatedPrice,
            actualPrice: $this->actualPrice,
            requestedAt: $this->requestedAt,
            acceptedAt: $this->acceptedAt,
            startedAt: CarbonImmutable::now(),
            completedAt: null,
            metadata: $this->metadata,
        );
    }

    public function complete(float $actualPrice): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            driverId: $this->driverId,
            pickupLocation: $this->pickupLocation,
            dropoffLocation: $this->dropoffLocation,
            status: RideStatus::completed(),
            estimatedPrice: $this->estimatedPrice,
            actualPrice: $actualPrice,
            requestedAt: $this->requestedAt,
            acceptedAt: $this->acceptedAt,
            startedAt: $this->startedAt,
            completedAt: CarbonImmutable::now(),
            metadata: $this->metadata,
        );
    }

    public function cancel(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            userId: $this->userId,
            driverId: $this->driverId,
            pickupLocation: $this->pickupLocation,
            dropoffLocation: $this->dropoffLocation,
            status: RideStatus::cancelled(),
            estimatedPrice: $this->estimatedPrice,
            actualPrice: $this->actualPrice,
            requestedAt: $this->requestedAt,
            acceptedAt: $this->acceptedAt,
            startedAt: $this->startedAt,
            completedAt: CarbonImmutable::now(),
            metadata: $this->metadata,
        );
    }

    public function getDistance(): float
    {
        return $this->pickupLocation->distanceTo($this->dropoffLocation);
    }

    public function isCompleted(): bool
    {
        return $this->status->value === RideStatus::COMPLETED;
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }
}
