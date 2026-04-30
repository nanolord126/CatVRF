<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\SeasonalPrograms;

use Modules\Fitness\Domain\SeasonalPrograms\Entities\ClientProgramEnrollment;
use Modules\Fitness\Domain\SeasonalPrograms\Repositories\ClientProgramEnrollmentRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\SeasonalPrograms\ClientProgramEnrollmentModel;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentClientProgramEnrollmentRepository implements ClientProgramEnrollmentRepositoryInterface
{
    public function save(ClientProgramEnrollment $enrollment): ClientProgramEnrollment
    {
        $model = $enrollment->id === 0
            ? ClientProgramEnrollmentModel::fromDomain($enrollment)
            : ClientProgramEnrollmentModel::where('id', $enrollment->id)->first();

        if ($model === null) {
            $model = ClientProgramEnrollmentModel::fromDomain($enrollment);
        } else {
            $model->updateFromDomain($enrollment);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?ClientProgramEnrollment
    {
        $model = ClientProgramEnrollmentModel::find($id);

        return $model?->toDomain();
    }

    public function findByClientId(int $clientId): Collection
    {
        $models = ClientProgramEnrollmentModel::where('client_id', $clientId)->get();

        return $models->map(fn (ClientProgramEnrollmentModel $model) => $model->toDomain());
    }

    public function findByProgramId(int $programId): Collection
    {
        $models = ClientProgramEnrollmentModel::where('seasonal_program_id', $programId)->get();

        return $models->map(fn (ClientProgramEnrollmentModel $model) => $model->toDomain());
    }

    public function findByProgramIdAndStatus(int $programId, string $status): Collection
    {
        $models = ClientProgramEnrollmentModel::where('seasonal_program_id', $programId)
            ->where('status', $status)
            ->get();

        return $models->map(fn (ClientProgramEnrollmentModel $model) => $model->toDomain());
    }

    public function findByClientIdAndProgramId(int $clientId, int $programId): ?ClientProgramEnrollment
    {
        $model = ClientProgramEnrollmentModel::where('client_id', $clientId)
            ->where('seasonal_program_id', $programId)
            ->first();

        return $model?->toDomain();
    }

    public function findActiveByClientId(int $clientId): array
    {
        $models = ClientProgramEnrollmentModel::where('client_id', $clientId)
            ->where('status', 'active')
            ->get();

        return $models->map(fn (ClientProgramEnrollmentModel $model) => $model->toDomain())->toArray();
    }

    public function delete(int $id): void
    {
        ClientProgramEnrollmentModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return ClientProgramEnrollmentModel::where('id', $id)->exists();
    }
}
