<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\WellnessMetrics;
use Modules\CatCRM\Domain\Staff\ValueObjects\WellnessMetricsId;
use Carbon\CarbonImmutable;

/**
 * WellnessMetricsRepositoryInterface — Interface for WellnessMetrics repository
 */
interface WellnessMetricsRepositoryInterface
{
    public function findById(WellnessMetricsId $id): ?WellnessMetrics;

    public function findByEmployee(int $tenantId, int $employeeId): array;

    public function findByEmployeeAndDateRange(
        int $tenantId,
        int $employeeId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array;

    public function findLatestByEmployee(int $tenantId, int $employeeId): ?WellnessMetrics;

    public function save(WellnessMetrics $metrics): bool;

    public function delete(WellnessMetricsId $id): bool;
}
