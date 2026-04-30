<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Entities;

use Carbon\CarbonImmutable;

final readonly class CorporatePackage
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $description,
        public float $pricePerEmployee,
        public int $minEmployees,
        public ?int $maxEmployees,
        public int $durationMonths,
        public ?array $includedServices,
        public ?array $features,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $description,
        float $pricePerEmployee,
        int $minEmployees,
        int $durationMonths,
        ?int $maxEmployees = null,
        ?array $includedServices = null,
        ?array $features = null,
        bool $isActive = true,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            pricePerEmployee: $pricePerEmployee,
            minEmployees: $minEmployees,
            maxEmployees: $maxEmployees,
            durationMonths: $durationMonths,
            includedServices: $includedServices,
            features: $features,
            isActive: $isActive,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function calculateTotalPrice(int $employeeCount): float
    {
        if ($this->maxEmployees !== null && $employeeCount > $this->maxEmployees) {
            throw new \InvalidArgumentException("Employee count exceeds maximum allowed: {$this->maxEmployees}");
        }

        return $this->pricePerEmployee * $employeeCount;
    }

    public function isValidEmployeeCount(int $count): bool
    {
        return $count >= $this->minEmployees 
            && ($this->maxEmployees === null || $count <= $this->maxEmployees);
    }
}
