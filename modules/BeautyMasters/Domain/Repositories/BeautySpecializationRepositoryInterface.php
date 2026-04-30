<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\BeautySpecialization;
use Illuminate\Support\Collection;

interface BeautySpecializationRepositoryInterface
{
    public function findById(int $id): ?BeautySpecialization;

    public function findByMasterId(int $masterId): Collection;

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?BeautySpecialization;

    public function save(BeautySpecialization $specialization): BeautySpecialization;

    public function delete(int $id): bool;
}
