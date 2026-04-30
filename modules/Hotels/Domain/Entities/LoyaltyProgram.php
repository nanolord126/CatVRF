<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Modules\Hotels\Domain\Enums\GuestLoyaltyLevel;
use Carbon\CarbonImmutable;

final readonly class LoyaltyProgram
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public string $uuid,
        public string $name,
        public ?string $description,
        public GuestLoyaltyLevel $level,
        public int $pointsPerNight,
        public float $pointsToRublesRate,
        public ?array $benefits,
        public bool $isActive,
        public ?CarbonImmutable $validFrom,
        public ?CarbonImmutable $validUntil,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        string $name,
        GuestLoyaltyLevel $level,
        int $pointsPerNight = 10,
        float $pointsToRublesRate = 0.01,
        ?string $description = null,
        ?array $benefits = null,
        ?CarbonImmutable $validFrom = null,
        ?CarbonImmutable $validUntil = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            name: $name,
            description: $description,
            level: $level,
            pointsPerNight: $pointsPerNight,
            pointsToRublesRate: $pointsToRublesRate,
            benefits: $benefits,
            isActive: true,
            validFrom: $validFrom,
            validUntil: $validUntil,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function activate(): self
    {
        return new self(
            ...get_object_vars($this),
            isActive: true,
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

    public function calculatePoints(int $nights): int
    {
        return $nights * $this->pointsPerNight;
    }

    public function pointsToRubles(int $points): float
    {
        return $points * $this->pointsToRublesRate;
    }

    public function rublesToPoints(float $rubles): int
    {
        return (int) floor($rubles / $this->pointsToRublesRate);
    }

    public function getDiscountPercent(): int
    {
        return $this->level->getDiscountPercent();
    }

    public function isValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->validFrom !== null && $this->validFrom->isFuture()) {
            return false;
        }

        if ($this->validUntil !== null && $this->validUntil->isPast()) {
            return false;
        }

        return true;
    }
}
