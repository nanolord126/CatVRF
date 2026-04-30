<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Repositories\SeasonalPrograms;

use Modules\Fitness\Domain\SeasonalPrograms\Entities\ProgramProgressLog;
use Modules\Fitness\Domain\SeasonalPrograms\Repositories\ProgramProgressLogRepositoryInterface;
use Modules\Fitness\Infrastructure\Models\SeasonalPrograms\ProgramProgressLogModel;
use Illuminate\Database\Eloquent\Collection;

final readonly class EloquentProgramProgressLogRepository implements ProgramProgressLogRepositoryInterface
{
    public function save(ProgramProgressLog $log): ProgramProgressLog
    {
        $model = $log->id === 0
            ? ProgramProgressLogModel::fromDomain($log)
            : ProgramProgressLogModel::where('id', $log->id)->first();

        if ($model === null) {
            $model = ProgramProgressLogModel::fromDomain($log);
        } else {
            $model->updateFromDomain($log);
        }

        $model->save();

        return $model->toDomain();
    }

    public function findById(int $id): ?ProgramProgressLog
    {
        $model = ProgramProgressLogModel::find($id);

        return $model?->toDomain();
    }

    public function findByEnrollmentId(int $enrollmentId): array
    {
        $models = ProgramProgressLogModel::where('enrollment_id', $enrollmentId)
            ->orderBy('date')
            ->get();

        return $models->map(fn (ProgramProgressLogModel $model) => $model->toDomain())->toArray();
    }

    public function findByEnrollmentIdAndDate(int $enrollmentId, string $date): ?ProgramProgressLog
    {
        $model = ProgramProgressLogModel::where('enrollment_id', $enrollmentId)
            ->where('date', $date)
            ->first();

        return $model?->toDomain();
    }

    public function findByEnrollmentIdBetween(
        int $enrollmentId,
        string $startDate,
        string $endDate
    ): array {
        $models = ProgramProgressLogModel::where('enrollment_id', $enrollmentId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return $models->map(fn (ProgramProgressLogModel $model) => $model->toDomain())->toArray();
    }

    public function getLatestByEnrollmentId(int $enrollmentId): ?ProgramProgressLog
    {
        $model = ProgramProgressLogModel::where('enrollment_id', $enrollmentId)
            ->orderBy('date', 'desc')
            ->first();

        return $model?->toDomain();
    }

    public function delete(int $id): void
    {
        ProgramProgressLogModel::where('id', $id)->delete();
    }

    public function exists(int $id): bool
    {
        return ProgramProgressLogModel::where('id', $id)->exists();
    }
}
