<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class BookingItem
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $bookingId,
        public int $roomId,
        public int $roomTypeId,
        public string $uuid,
        public CarbonImmutable $checkInDate,
        public CarbonImmutable $checkOutDate,
        public int $nights,
        public float $roomRate,
        public float $totalAmount,
        public ?array $guests,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $bookingId,
        int $roomId,
        int $roomTypeId,
        CarbonImmutable $checkInDate,
        CarbonImmutable $checkOutDate,
        float $roomRate,
        ?array $guests = null,
    ): self {
        $nights = $checkInDate->diffInDays($checkOutDate);
        
        return new self(
            id: 0,
            tenantId: $tenantId,
            bookingId: $bookingId,
            roomId: $roomId,
            roomTypeId: $roomTypeId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            checkInDate: $checkInDate,
            checkOutDate: $checkOutDate,
            nights: $nights,
            roomRate: $roomRate,
            totalAmount: $roomRate * $nights,
            guests: $guests,
            isActive: true,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...get_object_vars($this),
            isActive: false,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
