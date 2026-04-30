<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Repositories;

use Modules\Analytics\Domain\Entities\DailyMetrics;
use Modules\Analytics\Domain\Entities\HourlyMetrics;
use Modules\Analytics\Domain\ValueObjects\Period;
use Modules\Analytics\Domain\ValueObjects\TenantId;
use Modules\Analytics\Domain\ValueObjects\SellerId;

/**
 * Metrics Repository Interface
 *
 * Defines contract for metrics data access following Clean Architecture.
 */
interface MetricsRepositoryInterface
{
    /**
     * Find daily metrics for a tenant within a period.
     * 
     * @return DailyMetrics[]
     */
    public function findDailyMetrics(TenantId $tenantId, Period $period): array;

    /**
     * Find hourly metrics for a tenant within a period.
     * 
     * @return HourlyMetrics[]
     */
    public function findHourlyMetrics(TenantId $tenantId, Period $period): array;

    /**
     * Find seller metrics for a tenant within a period.
     * 
     * @return DailyMetrics[]
     */
    public function findSellerMetrics(TenantId $tenantId, SellerId $sellerId, Period $period): array;

    /**
     * Save daily metrics.
     */
    public function saveDailyMetrics(DailyMetrics $metrics): void;

    /**
     * Save hourly metrics.
     */
    public function saveHourlyMetrics(HourlyMetrics $metrics): void;

    /**
     * Upsert daily metrics (update or insert).
     */
    public function upsertDailyMetrics(DailyMetrics $metrics): void;
}
