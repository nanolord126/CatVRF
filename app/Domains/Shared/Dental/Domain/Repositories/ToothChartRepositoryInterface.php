<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Repositories;

use Modules\Dental\Domain\Entities\ToothChart;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\TenantId;

interface ToothChartRepositoryInterface
{
    public function save(ToothChart $toothChart): void;

    public function findById(ToothChartId $id): ?ToothChart;

    public function findByPatient(PatientId $patientId, bool $isPrimary = true): ?ToothChart;

    public function findByTenant(TenantId $tenantId): array;

    public function delete(ToothChartId $id): void;
}
