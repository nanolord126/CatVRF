<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Prenatal;

use Modules\Fitness\Domain\Prenatal\Entities\PrenatalEnrollment;
use Modules\Fitness\Domain\Prenatal\Repositories\PrenatalEnrollmentRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Prenatal\PrenatalEnrollmentModel;

final readonly class EloquentPrenatalEnrollmentRepository implements PrenatalEnrollmentRepositoryInterface
{
    public function save(PrenatalEnrollment $enrollment): PrenatalEnrollment
    {
        $model = $enrollment->id > 0
            ? PrenatalEnrollmentModel::findOrFail($enrollment->id)
            : new PrenatalEnrollmentModel();

        if ($enrollment->id > 0) {
            $model->updateFromDomain($enrollment);
        } else {
            $model = PrenatalEnrollmentModel::fromDomain($enrollment);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?PrenatalEnrollment
    {
        $model = PrenatalEnrollmentModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): array
    {
        $models = PrenatalEnrollmentModel::where('client_id', $clientId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByProgramId(int $programId): array
    {
        $models = PrenatalEnrollmentModel::where('prenatal_program_id', $programId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findPendingClearance(int $tenantId): array
    {
        $models = PrenatalEnrollmentModel::where('tenant_id', $tenantId)
            ->where('medical_clearance_status', 'pending')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByClientId(int $clientId): array
    {
        $models = PrenatalEnrollmentModel::where('client_id', $clientId)
            ->where('status', 'active')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        PrenatalEnrollmentModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return PrenatalEnrollmentModel::where('id', $id)->exists();
    }
}
