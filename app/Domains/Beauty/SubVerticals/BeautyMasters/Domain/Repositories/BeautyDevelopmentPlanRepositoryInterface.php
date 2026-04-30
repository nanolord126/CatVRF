<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\BeautyDevelopmentPlan;

interface BeautyDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?BeautyDevelopmentPlan;

    public function findByMasterId(int $masterId): ?BeautyDevelopmentPlan;

    public function save(BeautyDevelopmentPlan $plan): BeautyDevelopmentPlan;

    public function delete(int $id): bool;
}
