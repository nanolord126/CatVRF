<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\Kids;

use Modules\Fitness\Domain\Kids\Entities\KidsProgramEnrollment;
use Modules\Fitness\Domain\Kids\Repositories\KidsProgramEnrollmentRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\Kids\KidsProgramEnrollmentModel;

final readonly class EloquentKidsProgramEnrollmentRepository implements KidsProgramEnrollmentRepositoryInterface
{
    public function save(KidsProgramEnrollment $enrollment): KidsProgramEnrollment
    {
        $model = $enrollment->id > 0
            ? KidsProgramEnrollmentModel::findOrFail($enrollment->id)
            : new KidsProgramEnrollmentModel();

        if ($enrollment->id > 0) {
            $model->updateFromDomain($enrollment);
        } else {
            $model = KidsProgramEnrollmentModel::fromDomain($enrollment);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?KidsProgramEnrollment
    {
        $model = KidsProgramEnrollmentModel::find($id);
        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): array
    {
        $models = KidsProgramEnrollmentModel::where('client_id', $clientId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findByProgramId(int $programId): array
    {
        $models = KidsProgramEnrollmentModel::where('kids_program_id', $programId)->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findPendingClearance(int $tenantId): array
    {
        $models = KidsProgramEnrollmentModel::where('tenant_id', $tenantId)
            ->where('medical_clearance_status', 'pending')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function findActiveByClientId(int $clientId): array
    {
        $models = KidsProgramEnrollmentModel::where('client_id', $clientId)
            ->where('status', 'active')
            ->get();
        return $models->map(fn ($model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        KidsProgramEnrollmentModel::findOrFail($id)->delete();
    }

    public function exists(int $id): bool
    {
        return KidsProgramEnrollmentModel::where('id', $id)->exists();
    }
}
