<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Enums\WorkoutIntensity;

final readonly class WorkoutType
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public WorkoutIntensity $intensity,
        public int $durationMinutes,
        public int $caloriesBurnEstimate,
        public ?array $equipmentNeeded,
        public ?string $category,
        public bool $isGroup,
        public int $maxParticipants,
        public float $price,
        public ?string $icon,
        public ?string $color,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        WorkoutIntensity $intensity,
        int $durationMinutes = 60,
        int $caloriesBurnEstimate = 400,
        ?string $description = null,
        ?array $equipmentNeeded = null,
        ?string $category = null,
        bool $isGroup = false,
        int $maxParticipants = 1,
        float $price = 0.0,
        ?string $icon = null,
        ?string $color = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            intensity: $intensity,
            durationMinutes: $durationMinutes,
            caloriesBurnEstimate: $caloriesBurnEstimate,
            equipmentNeeded: $equipmentNeeded,
            category: $category,
            isGroup: $isGroup,
            maxParticipants: $maxParticipants,
            price: $price,
            icon: $icon,
            color: $color,
            isActive: true,
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

    public function requiresEquipment(string $equipment): bool
    {
        return in_array($equipment, $this->equipmentNeeded ?? [], true);
    }
}
