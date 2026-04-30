<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\Enums\KitchenStationType;
use Modules\Restaurant\Domain\ValueObjects\PreparationTime;
use Carbon\CarbonImmutable;

final readonly class KitchenStation
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public KitchenStationType $type,
        public bool $isActive,
        public ?string $description,
        public ?int $displayOrder,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        KitchenStationType $type,
        ?string $description = null,
        ?int $displayOrder = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            type: $type,
            isActive: true,
            description: $description,
            displayOrder: $displayOrder,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $name,
            type: $this->type,
            isActive: $this->isActive,
            description: $this->description,
            displayOrder: $this->displayOrder,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withActivation(bool $isActive): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            type: $this->type,
            isActive: $isActive,
            description: $this->description,
            displayOrder: $this->displayOrder,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
