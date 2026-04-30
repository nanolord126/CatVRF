<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\VetGrooming\Domain\Enums\ConditionType;

final readonly class PetChronicCondition
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $petId,
        public ?int $veterinarianId,
        public ConditionType $conditionType,
        public string $conditionName,
        public ?string $icdCode,
        public ?CarbonImmutable $diagnosedDate,
        public ?string $description,
        public ?string $severity,
        public bool $isActive,
        public ?CarbonImmutable $resolvedDate,
        public ?array $symptoms,
        public ?array $triggers,
        public ?array $managementNotes,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $petId,
        ConditionType $conditionType,
        string $conditionName,
        ?int $veterinarianId = null,
        ?string $icdCode = null,
        ?CarbonImmutable $diagnosedDate = null,
        ?string $description = null,
        ?string $severity = null,
        ?array $symptoms = null,
        ?array $triggers = null,
        ?array $managementNotes = null,
        ?string $correlationId = null,
        ?array $tags = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            petId: $petId,
            veterinarianId: $veterinarianId,
            conditionType: $conditionType,
            conditionName: $conditionName,
            icdCode: $icdCode,
            diagnosedDate: $diagnosedDate ?? CarbonImmutable::now(),
            description: $description,
            severity: $severity ?? 'moderate',
            isActive: true,
            resolvedDate: null,
            symptoms: $symptoms,
            triggers: $triggers,
            managementNotes: $managementNotes,
            correlationId: $correlationId,
            tags: $tags,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function resolve(?string $notes = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            petId: $this->petId,
            veterinarianId: $this->veterinarianId,
            conditionType: $this->conditionType,
            conditionName: $this->conditionName,
            icdCode: $this->icdCode,
            diagnosedDate: $this->diagnosedDate,
            description: $notes ?? $this->description,
            severity: $this->severity,
            isActive: false,
            resolvedDate: CarbonImmutable::now(),
            symptoms: $this->symptoms,
            triggers: $this->triggers,
            managementNotes: $this->managementNotes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function reactivate(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            petId: $this->petId,
            veterinarianId: $this->veterinarianId,
            conditionType: $this->conditionType,
            conditionName: $this->conditionName,
            icdCode: $this->icdCode,
            diagnosedDate: $this->diagnosedDate,
            description: $this->description,
            severity: $this->severity,
            isActive: true,
            resolvedDate: null,
            symptoms: $this->symptoms,
            triggers: $this->triggers,
            managementNotes: $this->managementNotes,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isAllergy(): bool
    {
        return in_array($this->conditionType, [
            ConditionType::ALLERGY_MEDICATION,
            ConditionType::ALLERGY_FOOD,
            ConditionType::ALLERGY_ENVIRONMENTAL,
        ]);
    }

    public function isAnesthesiaIntolerance(): bool
    {
        return $this->conditionType === ConditionType::ANESTHESIA_INTOLERANCE;
    }

    public function isCritical(): bool
    {
        return $this->severity === 'severe' || $this->isAnesthesiaIntolerance();
    }
}
