<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\BreedSpecialization;

interface BreedSpecializationRepositoryInterface
{
    public function findById(int $id): ?BreedSpecialization;

    public function findByUuid(string $uuid): ?BreedSpecialization;

    public function findByMasterId(int $masterId): array;

    public function findByMasterIdAndBreedGroup(int $masterId, string $breedGroup): ?BreedSpecialization;

    public function findActiveByMasterId(int $masterId): array;

    public function findByProficiencyLevel(string $level, int $tenantId): array;

    public function findByBreedGroup(string $breedGroup, int $tenantId): array;

    public function save(BreedSpecialization $specialization): BreedSpecialization;

    public function delete(int $id): void;

    public function deleteByMasterId(int $masterId): void;
}
