<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\BeautyCertification;
use Illuminate\Support\Collection;

interface BeautyCertificationRepositoryInterface
{
    public function findById(int $id): ?BeautyCertification;

    public function findByMasterId(int $masterId): Collection;

    public function findActiveByMasterId(int $masterId): Collection;

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?BeautyCertification;

    public function findExpiringSoon(int $daysThreshold = 60): Collection;

    public function findExpired(): Collection;

    public function save(BeautyCertification $certification): BeautyCertification;

    public function delete(int $id): bool;
}
