<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\MakeupSpecialization;
use Illuminate\Support\Collection;

interface MakeupSpecializationRepositoryInterface
{
    public function findById(int $id): ?MakeupSpecialization;

    public function findByMasterId(int $masterId): Collection;

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?MakeupSpecialization;

    public function save(MakeupSpecialization $specialization): MakeupSpecialization;

    public function delete(int $id): bool;
}
