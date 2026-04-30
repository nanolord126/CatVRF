<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\BrowCertification;
use Illuminate\Support\Collection;

interface BrowCertificationRepositoryInterface
{
    public function findById(int $id): ?BrowCertification;

    public function findByMasterId(int $masterId): Collection;

    public function findActiveByMasterId(int $masterId): Collection;

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?BrowCertification;

    public function findExpiringSoon(int $daysThreshold = 60): Collection;

    public function findExpired(): Collection;

    public function save(BrowCertification $certification): BrowCertification;

    public function delete(int $id): bool;
}
