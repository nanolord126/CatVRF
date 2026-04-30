<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\BrowDevelopmentPlan;

interface BrowDevelopmentPlanRepositoryInterface
{
    public function findById(int $id): ?BrowDevelopmentPlan;

    public function findByMasterId(int $masterId): ?BrowDevelopmentPlan;

    public function save(BrowDevelopmentPlan $plan): BrowDevelopmentPlan;

    public function delete(int $id): bool;
}
