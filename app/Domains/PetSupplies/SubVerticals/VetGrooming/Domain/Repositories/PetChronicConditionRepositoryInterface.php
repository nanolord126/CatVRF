<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\PetChronicCondition;
use Modules\VetGrooming\Domain\Enums\ConditionType;

interface PetChronicConditionRepositoryInterface
{
    public function findById(int $id): ?PetChronicCondition;

    public function findByUuid(string $uuid): ?PetChronicCondition;

    public function findByPetId(int $petId): array;

    public function findActiveByPetId(int $petId): array;

    public function findByPetIdAndConditionType(int $petId, ConditionType $conditionType): array;

    public function findActiveByPetIdAndConditionType(int $petId, ConditionType $conditionType): array;

    public function findAllergiesByPetId(int $petId): array;

    public function findActiveAllergiesByPetId(int $petId): array;

    public function findChronicDiseasesByPetId(int $petId): array;

    public function findAnesthesiaIntolerancesByPetId(int $petId): array;

    public function findCriticalConditionsByPetId(int $petId): array;

    public function findResolvedByPetId(int $petId): array;

    public function save(PetChronicCondition $condition): PetChronicCondition;

    public function delete(int $id): void;

    public function countActiveByPetId(int $petId): int;
}
