<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\MakeupDevelopmentPlan;

interface MakeupDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?MakeupDevelopmentPlan;

    public function findByMasterId(int $masterId): ?MakeupDevelopmentPlan;

    public function save(MakeupDevelopmentPlan $plan): MakeupDevelopmentPlan;

    public function delete(int $id): bool;
}
