<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\LashCertification;
use Illuminate\Support\Collection;

interface LashCertificationRepositoryInterface
{
    public function findById(int $id): ?LashCertification;

    public function findByMasterId(int $masterId): Collection;

    public function findActiveByMasterId(int $masterId): Collection;

    public function findActiveByMasterIdAndSpecialization(int $masterId, string $specialization): ?LashCertification;

    public function findExpiringSoon(int $daysThreshold = 60): Collection;

    public function findExpired(): Collection;

    public function save(LashCertification $certification): LashCertification;

    public function delete(int $id): bool;
}
