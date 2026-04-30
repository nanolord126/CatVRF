<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\BreedDevelopmentPlan;
use Carbon\CarbonImmutable;

interface BreedDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?BreedDevelopmentPlan;

    public function findByUuid(string $uuid): ?BreedDevelopmentPlan;

    public function findByMasterId(int $masterId): array;

    public function findByMasterIdAndBreedGroup(int $masterId, string $breedGroup): ?BreedDevelopmentPlan;

    public function findByMasterIdAndBreed(int $masterId, string $breed): ?BreedDevelopmentPlan;

    public function findByStatus(string $status, int $tenantId): array;

    public function findInProgressByMasterId(int $masterId): array;

    public function findReadyForExam(int $tenantId): array;

    public function findOverduePlans(int $tenantId): array;

    public function findByMentorId(int $mentorId): array;

    public function save(BreedDevelopmentPlan $plan): BreedDevelopmentPlan;

    public function delete(int $id): void;
}
