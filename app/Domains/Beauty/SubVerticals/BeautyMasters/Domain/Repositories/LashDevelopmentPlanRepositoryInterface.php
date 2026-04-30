<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\LashDevelopmentPlan;

interface LashDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?LashDevelopmentPlan;

    public function findByMasterId(int $masterId): ?LashDevelopmentPlan;

    public function save(LashDevelopmentPlan $plan): LashDevelopmentPlan;

    public function delete(int $id): bool;
}
