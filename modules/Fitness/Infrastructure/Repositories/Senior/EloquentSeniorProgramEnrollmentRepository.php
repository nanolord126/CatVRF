<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Senior;

use Modules\Fitness\Domain\Senior\Entities\SeniorProgramEnrollment;
use Modules\Fitness\Domain\Senior\Repositories\SeniorProgramEnrollmentRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorProgramEnrollmentModel;

final readonly class EloquentSeniorProgramEnrollmentRepository implements SeniorProgramEnrollmentRepositoryInterface
{
    public function save(SeniorProgramEnrollment $enrollment): SeniorProgramEnrollment
    {
        $model = $enrollment->id === 0
            ? SeniorProgramEnrollmentModel::fromDomain($enrollment)
            : SeniorProgramEnrollmentModel::where('id', $enrollment->id)->first();

        if ($model === null) {
            $model = SeniorProgramEnrollmentModel::fromDomain($enrollment);
        } else {
            $model->updateFromDomain($enrollment);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?SeniorProgramEnrollment
    {
        $model = SeniorProgramEnrollmentModel::find($id);

        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): array
    {
        $models = SeniorProgramEnrollmentModel::where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn (SeniorProgramEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findByProgramId(int $programId): array
    {
        $models = SeniorProgramEnrollmentModel::where('senior_program_id', $programId)->get();

        return $models->map(fn (SeniorProgramEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findPendingClearance(int $tenantId): array
    {
        $models = SeniorProgramEnrollmentModel::where('tenant_id', $tenantId)
            ->where('medical_clearance_status', 'pending')
            ->get();

        return $models->map(fn (SeniorProgramEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function findActiveByClientId(int $clientId): array
    {
        $models = SeniorProgramEnrollmentModel::where('client_id', $clientId)
            ->where('status', 'active')
            ->get();

        return $models->map(fn (SeniorProgramEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        SeniorProgramEnrollmentModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return SeniorProgramEnrollmentModel::where('id', $id)->exists();
    }
}
