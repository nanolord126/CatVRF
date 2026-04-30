<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Modules\Hotels\Domain\Enums\ServiceType;
use Carbon\CarbonImmutable;

final readonly class Service
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public string $uuid,
        public string $name,
        public ?string $description,
        public ServiceType $type,
        public float $basePrice,
        public string $currency,
        public bool $isAvailable,
        public bool $isOptional,
        public ?string $icon,
        public ?array $pricingRules,
        public ?array $availabilityRules,
        public ?int $durationMinutes,
        public ?int $maxQuantityPerBooking,
        public int $sortOrder,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        string $name,
        ServiceType $type,
        float $basePrice,
        string $currency = 'RUB',
        ?string $description = null,
        bool $isAvailable = true,
        bool $isOptional = true,
        ?string $icon = null,
        ?int $durationMinutes = null,
        ?int $maxQuantityPerBooking = null,
        int $sortOrder = 0,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            name: $name,
            description: $description,
            type: $type,
            basePrice: $basePrice,
            currency: $currency,
            isAvailable: $isAvailable,
            isOptional: $isOptional,
            icon: $icon,
            pricingRules: null,
            availabilityRules: null,
            durationMinutes: $durationMinutes,
            maxQuantityPerBooking: $maxQuantityPerBooking,
            sortOrder: $sortOrder,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function activate(): self
    {
        return new self(
            ...get_object_vars($this),
            isAvailable: true,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...get_object_vars($this),
            isAvailable: false,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updatePrice(float $newPrice): self
    {
        return new self(
            ...get_object_vars($this),
            basePrice: $newPrice,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isFree(): bool
    {
        return $this->basePrice === 0.0;
    }

    public function calculatePrice(int $quantity = 1): float
    {
        return $this->basePrice * $quantity;
    }
}
