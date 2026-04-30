<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\PetVaccination;
use Modules\VetGrooming\Domain\Enums\VaccineType;
use Modules\VetGrooming\Domain\Enums\VaccinationStatus;
use Carbon\CarbonImmutable;

interface PetVaccinationRepositoryInterface
{
    public function findById(int $id): ?PetVaccination;

    public function findByUuid(string $uuid): ?PetVaccination;

    public function findByPetId(int $petId): array;

    public function findByPetIdAndVaccineType(int $petId, VaccineType $vaccineType): array;

    public function findOverdueByPetId(int $petId): array;

    public function findDueWithinDays(int $petId, int $days): array;

    public function findDueVaccinationsByTenant(int $tenantId, int $limit = 100): array;

    public function findOverdueVaccinationsByTenant(int $tenantId, int $limit = 100): array;

    public function findCompletedByPetId(int $petId): array;

    public function findLastVaccination(int $petId, VaccineType $vaccineType): ?PetVaccination;

    public function findNextDueVaccination(int $petId): ?PetVaccination;

    public function findRabiesVaccinations(int $petId): array;

    public function findByStatusAndTenant(VaccinationStatus $status, int $tenantId, int $limit = 100): array;

    public function save(PetVaccination $vaccination): PetVaccination;

    public function delete(int $id): void;

    public function countByPetId(int $petId): int;
}
