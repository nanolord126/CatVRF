<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Modules\VetGrooming\Domain\Enums\ExoticCategory;
use Modules\VetGrooming\Domain\Enums\ExoticGroup;
use Carbon\CarbonImmutable;

final readonly class ExoticSafetyProtocol
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $description,
        public ExoticCategory $category,
        public ?string $subcategory,
        public ExoticGroup $group,
        public string $species,
        public array $checklistItems,
        public ?array $riskFactors,
        public ?array $requiredEquipment,
        public ?array $emergencyProcedures,
        public bool $isActive,
        public int $version,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $description,
        ExoticCategory $category,
        ?string $subcategory,
        ExoticGroup $group,
        string $species,
        array $checklistItems,
        ?array $riskFactors = null,
        ?array $requiredEquipment = null,
        ?array $emergencyProcedures = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            category: $category,
            subcategory: $subcategory,
            group: $group,
            species: $species,
            checklistItems: $checklistItems,
            riskFactors: $riskFactors,
            requiredEquipment: $requiredEquipment,
            emergencyProcedures: $emergencyProcedures,
            isActive: true,
            version: 1,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isForCategory(ExoticCategory $category): bool
    {
        return $this->category === $category;
    }

    public function isForGroup(ExoticGroup $group): bool
    {
        return $this->group === $group;
    }

    public function isForSpecies(string $species): bool
    {
        return $this->species === $species;
    }

    public function getChecklistCount(): int
    {
        return count($this->checklistItems);
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            category: $this->category,
            subcategory: $this->subcategory,
            group: $this->group,
            species: $this->species,
            checklistItems: $this->checklistItems,
            riskFactors: $this->riskFactors,
            requiredEquipment: $this->requiredEquipment,
            emergencyProcedures: $this->emergencyProcedures,
            isActive: false,
            version: $this->version,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateChecklist(array $newChecklistItems): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            category: $this->category,
            subcategory: $this->subcategory,
            group: $this->group,
            species: $this->species,
            checklistItems: $newChecklistItems,
            riskFactors: $this->riskFactors,
            requiredEquipment: $this->requiredEquipment,
            emergencyProcedures: $this->emergencyProcedures,
            isActive: $this->isActive,
            version: $this->version + 1,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }
}
