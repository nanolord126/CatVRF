<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Repositories;

use Modules\Fitness\Domain\Entities\WorkoutType;

interface WorkoutTypeRepositoryInterface
{
    public function findById(int $id): ?WorkoutType;

    public function findByTenantId(int $tenantId): array;

    public function findActiveByTenantId(int $tenantId): array;

    public function findByCategory(int $tenantId, string $category): array;

    public function findByIntensity(int $tenantId, string $intensity): array;

    public function findGroupWorkouts(int $tenantId): array;

    public function save(WorkoutType $workoutType): WorkoutType;

    public function delete(int $id): void;
}
