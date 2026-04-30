<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Corporate;

use Modules\Fitness\Domain\Corporate\Entities\CorporateReport;
use Modules\Fitness\Domain\Corporate\Repositories\CorporateReportRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporateReportModel;

final readonly class EloquentCorporateReportRepository implements CorporateReportRepositoryInterface
{
    public function save(CorporateReport $report): CorporateReport
    {
        $model = $report->id === 0
            ? CorporateReportModel::fromDomain($report)
            : CorporateReportModel::where('id', $report->id)->first();

        if ($model === null) {
            $model = CorporateReportModel::fromDomain($report);
        } else {
            $model->updateFromDomain($report);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?CorporateReport
    {
        $model = CorporateReportModel::find($id);

        return $model?->toDomain();
    }

    public function findByCorporateEnrollmentId(int $corporateEnrollmentId): array
    {
        $models = CorporateReportModel::where('corporate_enrollment_id', $corporateEnrollmentId)
            ->orderBy('period_start', 'desc')
            ->get();

        return $models->map(fn (CorporateReportModel $model) => $model->toDomain())->toArray();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = CorporateReportModel::where('tenant_id', $tenantId)
            ->orderBy('period_start', 'desc')
            ->get();

        return $models->map(fn (CorporateReportModel $model) => $model->toDomain())->toArray();
    }

    public function findByPeriod(int $tenantId, string $startDate, string $endDate): array
    {
        $models = CorporateReportModel::where('tenant_id', $tenantId)
            ->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate)
            ->get();

        return $models->map(fn (CorporateReportModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        CorporateReportModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return CorporateReportModel::where('id', $id)->exists();
    }
}
