<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\MakeupCertification;
use Illuminate\Support\Collection;

interface MakeupCertificationRepositoryInterface
{
    public function findById(int $id): ?MakeupCertification;

    public function findByMasterId(int $masterId): Collection;

    public function findActiveByMasterId(int $masterId): Collection;

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?MakeupCertification;

    public function findExpiringSoon(int $daysThreshold = 60): Collection;

    public function findExpired(): Collection;

    public function save(MakeupCertification $certification): MakeupCertification;

    public function delete(int $id): bool;
}
