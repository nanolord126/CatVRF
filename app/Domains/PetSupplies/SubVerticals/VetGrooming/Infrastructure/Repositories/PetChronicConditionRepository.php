<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Repositories\PetChronicConditionRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\PetChronicCondition;
use Modules\VetGrooming\Domain\Enums\ConditionType;
use Modules\VetGrooming\Infrastructure\Models\PetChronicConditionModel;

class PetChronicConditionRepository implements PetChronicConditionRepositoryInterface
{
    public function findById(int $id): ?PetChronicCondition
    {
        $model = PetChronicConditionModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?PetChronicCondition
    {
        $model = PetChronicConditionModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where('is_active', true)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByPetIdAndConditionType(int $petId, ConditionType $conditionType): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where('condition_type', $conditionType->value)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByPetIdAndConditionType(int $petId, ConditionType $conditionType): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where('condition_type', $conditionType->value)
            ->where('is_active', true)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findAllergiesByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->whereIn('condition_type', [
                ConditionType::ALLERGY_MEDICATION->value,
                ConditionType::ALLERGY_FOOD->value,
                ConditionType::ALLERGY_ENVIRONMENTAL->value,
            ])
            ->where('is_active', true)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveAllergiesByPetId(int $petId): array
    {
        return $this->findAllergiesByPetId($petId);
    }

    public function findChronicDiseasesByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where('condition_type', ConditionType::CHRONIC_DISEASE->value)
            ->where('is_active', true)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findAnesthesiaIntolerancesByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where('condition_type', ConditionType::ANESTHESIA_INTOLERANCE->value)
            ->where('is_active', true)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findCriticalConditionsByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where(function ($query) {
                $query->where('condition_type', ConditionType::ANESTHESIA_INTOLERANCE->value)
                    ->orWhere('severity', 'severe');
            })
            ->where('is_active', true)
            ->orderBy('diagnosed_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findResolvedByPetId(int $petId): array
    {
        $models = PetChronicConditionModel::where('pet_id', $petId)
            ->where('is_active', false)
            ->orderBy('resolved_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(PetChronicCondition $condition): PetChronicCondition
    {
        $model = PetChronicConditionModel::fromDomain($condition);
        $model->save();

        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        PetChronicConditionModel::destroy($id);
    }

    public function countActiveByPetId(int $petId): int
    {
        return PetChronicConditionModel::where('pet_id', $petId)
            ->where('is_active', true)
            ->count();
    }
}
