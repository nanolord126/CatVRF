<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Repositories;

use Modules\VetGrooming\Domain\Repositories\PetVaccinationRepositoryInterface;
use Modules\VetGrooming\Domain\Entities\PetVaccination;
use Modules\VetGrooming\Domain\Enums\VaccineType;
use Modules\VetGrooming\Domain\Enums\VaccinationStatus;
use Modules\VetGrooming\Infrastructure\Models\PetVaccinationModel;
use Carbon\CarbonImmutable;

class PetVaccinationRepository implements PetVaccinationRepositoryInterface
{
    public function findById(int $id): ?PetVaccination
    {
        $model = PetVaccinationModel::find($id);
        return $model?->toDomain();
    }

    public function findByUuid(string $uuid): ?PetVaccination
    {
        $model = PetVaccinationModel::where('uuid', $uuid)->first();
        return $model?->toDomain();
    }

    public function findByPetId(int $petId): array
    {
        $models = PetVaccinationModel::where('pet_id', $petId)
            ->orderBy('planned_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByPetIdAndVaccineType(int $petId, VaccineType $vaccineType): array
    {
        $models = PetVaccinationModel::where('pet_id', $petId)
            ->where('vaccine_type', $vaccineType->value)
            ->orderBy('planned_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findOverdueByPetId(int $petId): array
    {
        $models = PetVaccinationModel::where('pet_id', $petId)
            ->where(function ($query) {
                $query->where('status', VaccinationStatus::OVERDUE->value)
                    ->orWhere(function ($q) {
                        $q->where('next_due_date', '<', now())
                            ->where('status', '!=', VaccinationStatus::COMPLETED->value);
                    });
            })
            ->orderBy('next_due_date', 'asc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findDueWithinDays(int $petId, int $days): array
    {
        $models = PetVaccinationModel::where('pet_id', $petId)
            ->where('status', '!=', VaccinationStatus::COMPLETED->value)
            ->where('next_due_date', '<=', now()->addDays($days))
            ->where('next_due_date', '>=', now())
            ->orderBy('next_due_date', 'asc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findDueVaccinationsByTenant(int $tenantId, int $limit = 100): array
    {
        $models = PetVaccinationModel::where('tenant_id', $tenantId)
            ->where('status', VaccinationStatus::PLANNED->value)
            ->where('next_due_date', '<=', now()->addDays(30))
            ->orderBy('next_due_date', 'asc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findOverdueVaccinationsByTenant(int $tenantId, int $limit = 100): array
    {
        $models = PetVaccinationModel::where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->where('status', VaccinationStatus::OVERDUE->value)
                    ->orWhere(function ($q) {
                        $q->where('next_due_date', '<', now())
                            ->where('status', '!=', VaccinationStatus::COMPLETED->value);
                    });
            })
            ->orderBy('next_due_date', 'asc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findCompletedByPetId(int $petId): array
    {
        $models = PetVaccinationModel::where('pet_id', $petId)
            ->where('status', VaccinationStatus::COMPLETED->value)
            ->orderBy('actual_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findLastVaccination(int $petId, VaccineType $vaccineType): ?PetVaccination
    {
        $model = PetVaccinationModel::where('pet_id', $petId)
            ->where('vaccine_type', $vaccineType->value)
            ->where('status', VaccinationStatus::COMPLETED->value)
            ->orderBy('actual_date', 'desc')
            ->first();

        return $model?->toDomain();
    }

    public function findNextDueVaccination(int $petId): ?PetVaccination
    {
        $model = PetVaccinationModel::where('pet_id', $petId)
            ->where('status', VaccinationStatus::PLANNED->value)
            ->where('next_due_date', '>=', now())
            ->orderBy('next_due_date', 'asc')
            ->first();

        return $model?->toDomain();
    }

    public function findRabiesVaccinations(int $petId): array
    {
        $models = PetVaccinationModel::where('pet_id', $petId)
            ->where('vaccine_type', VaccineType::RABIES->value)
            ->orderBy('actual_date', 'desc')
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByStatusAndTenant(VaccinationStatus $status, int $tenantId, int $limit = 100): array
    {
        $models = PetVaccinationModel::where('tenant_id', $tenantId)
            ->where('status', $status->value)
            ->orderBy('planned_date', 'desc')
            ->limit($limit)
            ->get();

        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function save(PetVaccination $vaccination): PetVaccination
    {
        $model = PetVaccinationModel::fromDomain($vaccination);
        $model->save();

        return $model->toDomain();
    }

    public function delete(int $id): void
    {
        PetVaccinationModel::destroy($id);
    }

    public function countByPetId(int $petId): int
    {
        return PetVaccinationModel::where('pet_id', $petId)->count();
    }
}
