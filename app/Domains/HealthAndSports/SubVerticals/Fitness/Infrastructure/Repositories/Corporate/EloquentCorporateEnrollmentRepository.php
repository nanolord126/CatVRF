<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Corporate;

use Modules\Fitness\Domain\Corporate\Entities\CorporateEnrollment;
use Modules\Fitness\Domain\Corporate\Repositories\CorporateEnrollmentRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Corporate\CorporateEnrollmentModel;

final readonly class EloquentCorporateEnrollmentRepository implements CorporateEnrollmentRepositoryInterface
{
    public function save(CorporateEnrollment $enrollment): CorporateEnrollment
    {
        $model = $enrollment->id === 0
            ? CorporateEnrollmentModel::fromDomain($enrollment)
            : CorporateEnrollmentModel::where('id', $enrollment->id)->first();

        if ($model === null) {
            $model = CorporateEnrollmentModel::fromDomain($enrollment);
        } else {
            $model->updateFromDomain($enrollment);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?CorporateEnrollment
    {
        $model = CorporateEnrollmentModel::find($id);

        return $model?->toDomain();
    }

    public function findByTenantId(int $tenantId): array
    {
        $models = CorporateEnrollmentModel::where('tenant_id', $tenantId)->get();

        return $models->map(fn (CorporateEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findByCorporateClientId(int $corporateClientId): array
    {
        $models = CorporateEnrollmentModel::where('corporate_client_id', $corporateClientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn (CorporateEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findByClientId(int $clientId): array
    {
        $models = CorporateEnrollmentModel::whereHas('employeeMemberships', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->get();

        return $models->map(fn (CorporateEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByTenantId(int $tenantId): array
    {
        $models = CorporateEnrollmentModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();

        return $models->map(fn (CorporateEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findExpiringSoon(int $tenantId, int $daysThreshold = 30): array
    {
        $models = CorporateEnrollmentModel::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays($daysThreshold))
            ->where('end_date', '>', now())
            ->get();

        return $models->map(fn (CorporateEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        CorporateEnrollmentModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return CorporateEnrollmentModel::where('id', $id)->exists();
    }
}
