<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\BrowSpecialization;
use Illuminate\Support\Collection;

interface BrowSpecializationRepositoryInterface
{
    public function findById(int $id): ?BrowSpecialization;

    public function findByMasterId(int $masterId): Collection;

    public function findByMasterIdAndSpecialization(int $masterId, string $specialization): ?BrowSpecialization;

    public function save(BrowSpecialization $specialization): BrowSpecialization;

    public function delete(int $id): bool;
}
