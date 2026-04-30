<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Repositories;

use Modules\VetGrooming\Domain\Entities\ProfessionalDevelopmentPlan;
use Carbon\CarbonImmutable;

interface ProfessionalDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?ProfessionalDevelopmentPlan;

    public function findByUuid(string $uuid): ?ProfessionalDevelopmentPlan;

    public function findByMasterId(int $masterId): ?ProfessionalDevelopmentPlan;

    public function findByMasterIdAndProfession(int $masterId, string $professionType): ?ProfessionalDevelopmentPlan;

    public function findActiveByMasterId(int $masterId): ?ProfessionalDevelopmentPlan;

    public function findByTenantId(int $tenantId): array;

    public function findByStatus(string $status, int $tenantId): array;

    public function findOverduePlans(int $tenantId): array;

    public function findPlansRequiringReview(int $tenantId, int $daysThreshold = 7): array;

    public function save(ProfessionalDevelopmentPlan $plan): ProfessionalDevelopmentPlan;

    public function delete(int $id): void;
}
