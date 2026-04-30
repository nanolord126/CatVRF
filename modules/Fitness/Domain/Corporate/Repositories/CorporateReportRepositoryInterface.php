<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Repositories;

use Modules\Fitness\Domain\Corporate\Entities\CorporateReport;

interface CorporateReportRepositoryInterface
{
    public function save(CorporateReport $report): CorporateReport;

    public function findById(int $id): ?CorporateReport;

    public function findByCorporateEnrollmentId(int $corporateEnrollmentId): array;

    public function findByTenantId(int $tenantId): array;

    public function findByPeriod(int $tenantId, string $startDate, string $endDate): array;

    public function delete(int $id): void;

    public function exists(int $id): bool;
}
