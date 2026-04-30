<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;

final readonly class PropertyBooking
{
    public function __construct(
        public readonly ?int $id,
        public readonly PropertyId $propertyId,
        public readonly int $userId,
        public readonly CarbonImmutable $checkIn,
        public readonly CarbonImmutable $checkOut,
        public readonly string $status,
        public readonly float $totalPrice,
        public readonly array $metadata,
        public readonly CarbonImmutable $createdAt,
    ) {}

    public static function create(
        PropertyId $propertyId,
        int $userId,
        CarbonImmutable $checkIn,
        CarbonImmutable $checkOut,
        float $totalPrice,
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            propertyId: $propertyId,
            userId: $userId,
            checkIn: $checkIn,
            checkOut: $checkOut,
            status: 'pending',
            totalPrice: $totalPrice,
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
        );
    }

    public function confirm(): self
    {
        return new self(
            id: $this->id,
            propertyId: $this->propertyId,
            userId: $this->userId,
            checkIn: $this->checkIn,
            checkOut: $this->checkOut,
            status: 'confirmed',
            totalPrice: $this->totalPrice,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
        );
    }

    public function cancel(): self
    {
        return new self(
            id: $this->id,
            propertyId: $this->propertyId,
            userId: $this->userId,
            checkIn: $this->checkIn,
            checkOut: $this->checkOut,
            status: 'cancelled',
            totalPrice: $this->totalPrice,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
        );
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getNights(): int
    {
        return $this->checkIn->diffInDays($this->checkOut);
    }
}
