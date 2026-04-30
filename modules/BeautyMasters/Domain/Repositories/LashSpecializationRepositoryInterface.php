<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\LashSpecialization;
use Illuminate\Support\Collection;

interface LashSpecializationRepositoryInterface
{
    public function findById(int $id): ?LashSpecialization;

    public function findByMasterId(int $masterId): Collection;

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?LashSpecialization;

    public function save(LashSpecialization $specialization): LashSpecialization;

    public function delete(int $id): bool;
}
