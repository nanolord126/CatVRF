<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Repositories;

use Modules\BeautyMasters\Domain\Entities\CertificationTestResult;
use Illuminate\Support\Collection;

interface CertificationTestResultRepositoryInterface
{
    public function findById(int $id): ?CertificationTestResult;

    public function findByMasterId(int $masterId): Collection;

    public function findByMasterIdAndVertical(int $masterId, string $vertical): Collection;

    public function save(CertificationTestResult $result): CertificationTestResult;

    public function delete(int $id): bool;
}
